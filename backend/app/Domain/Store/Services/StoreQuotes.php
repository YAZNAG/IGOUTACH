<?php

declare(strict_types=1);

namespace App\Domain\Store\Services;

use App\Domain\Sales\Models\Sale;
use App\Domain\Store\Models\StoreOrder;
use App\Domain\Store\Models\StoreQuote;
use App\Domain\Store\Models\StoreSetting;
use App\Domain\Tax\Services\TaxeDuLieu;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Les devis de la boutique, du premier clic du client à la commande.
 *
 *   demande  →  chiffrage par la boutique  →  envoi  →  réponse du client
 *
 * Une demande ne contrôle pas le stock : c'est tout l'intérêt du gros, on
 * demande ce dont on a besoin et le vendeur dit s'il peut suivre. Le stock
 * disponible au moment de la demande est tout de même enregistré, pour que
 * celui qui chiffre sache de quoi il part.
 */
final class StoreQuotes
{
    public function __construct(
        private readonly StoreCatalog $catalogue,
        private readonly StorePricing $tarifs,
        private readonly DocumentNumberGeneratorInterface $numeros,
    ) {}

    /**
     * Demande venue du site. Les prix enregistrés sont indicatifs : ils
     * disent ce que le barème donnerait, pas ce qui sera accordé.
     *
     * @param  array{customer_name: string, customer_phone: string, customer_email?: string|null, company?: string|null, city: string, address?: string|null, note?: string|null, lines: list<array{product_id: int, quantity: int}>}  $data
     */
    public function demander(array $data, ?string $ip = null): StoreQuote
    {
        $ids = array_values(array_unique(array_map(static fn (array $l): int => (int) $l['product_id'], $data['lines'])));
        $articles = $this->catalogue->vendables()->whereIn('products.id', $ids)->get()->keyBy('id');
        $this->catalogue->precharger($articles->values()->all());

        $lignes = [];
        $sousTotal = 0.0;

        foreach ($data['lines'] as $l) {
            $article = $articles->get((int) $l['product_id']);
            if ($article === null) {
                throw new RuntimeException('Un article de la demande n’est plus au catalogue.');
            }

            $qte = max(1, (int) $l['quantity']);
            ['price' => $prix, 'code' => $palier] = $this->tarifs->pour($article, $qte);

            $lignes[] = [
                'product_id' => $article->id,
                'name' => $article->name,
                'sku' => $article->sku,
                'quantity' => $qte,
                'unit_price' => $prix,
                'price_type_code' => $palier,
                'line_total' => round($prix * $qte, 2),
                'stock_at_request' => $this->catalogue->stockDe($article),
            ];
            $sousTotal += $prix * $qte;
        }

        if ($lignes === []) {
            throw new RuntimeException('La demande est vide.');
        }

        return DB::transaction(function () use ($data, $lignes, $sousTotal, $ip): StoreQuote {
            $devis = StoreQuote::query()->create([
                'number' => $this->numero(),
                'status' => StoreQuote::STATUS_REQUESTED,
                'public_token' => Str::random(48),
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => preg_replace('/\s+/', '', $data['customer_phone']) ?? $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'company' => $data['company'] ?? null,
                'city' => trim($data['city']),
                'address' => $data['address'] ?? null,
                'note' => $data['note'] ?? null,
                'subtotal' => round($sousTotal, 2),
                'shipping_fee' => 0,
                'total' => round($sousTotal, 2),
                'ip' => $ip,
            ]);

            foreach ($lignes as $ligne) {
                $devis->lines()->create($ligne);
            }

            return $devis->load('lines');
        });
    }

    /**
     * Chiffrage par la boutique : prix ferme ligne à ligne, port, validité.
     * Les lignes absentes du tableau transmis sont retirées du devis — c'est
     * ainsi qu'on refuse un article sans refuser toute la demande.
     *
     * @param  array{lines: list<array{id: int, quantity: int, unit_price: float}>, shipping_fee?: float, valid_until?: string|null, reply?: string|null, warehouse_id?: int|null}  $data
     */
    public function chiffrer(StoreQuote $devis, array $data, ?int $userId): StoreQuote
    {
        if (in_array($devis->status, [StoreQuote::STATUS_ACCEPTED, StoreQuote::STATUS_CANCELLED], true)) {
            throw new RuntimeException('Ce devis est clos : il ne se modifie plus.');
        }

        $devis->loadMissing('lines');
        $parId = $devis->lines->keyBy('id');
        $gardees = [];
        $sousTotal = 0.0;

        foreach ($data['lines'] as $l) {
            $ligne = $parId->get((int) $l['id']);
            if ($ligne === null) {
                continue;
            }

            $qte = max(1, (int) $l['quantity']);
            $prix = round(max(0, (float) $l['unit_price']), 2);

            $ligne->update([
                'quantity' => $qte,
                'unit_price' => $prix,
                'line_total' => round($prix * $qte, 2),
            ]);

            $gardees[] = $ligne->id;
            $sousTotal += $prix * $qte;
        }

        if ($gardees === []) {
            throw new RuntimeException('Un devis sans ligne ne s’envoie pas.');
        }

        $port = round(max(0, (float) ($data['shipping_fee'] ?? $devis->shipping_fee)), 2);

        return DB::transaction(function () use ($devis, $data, $gardees, $sousTotal, $port, $userId): StoreQuote {
            $devis->lines()->whereNotIn('id', $gardees)->delete();

            $devis->update([
                'subtotal' => round($sousTotal, 2),
                'shipping_fee' => $port,
                'total' => round($sousTotal + $port, 2),
                'valid_until' => $data['valid_until'] ?? $devis->valid_until ?? $this->validiteParDefaut(),
                'reply' => $data['reply'] ?? $devis->reply,
                'warehouse_id' => $data['warehouse_id'] ?? $devis->warehouse_id,
                'handled_by' => $userId,
            ]);

            return $devis->refresh()->load('lines');
        });
    }

    /**
     * Envoi au client : le devis devient opposable et le lien public s'ouvre.
     * C'est aussi le moment où il apparaît dans les devis de la gestion, là
     * où les commerciaux travaillent déjà.
     */
    public function envoyer(StoreQuote $devis, ?int $userId): StoreQuote
    {
        if ($devis->status === StoreQuote::STATUS_ACCEPTED) {
            throw new RuntimeException('Ce devis est déjà accepté.');
        }

        $devis->loadMissing('lines');

        if ($devis->lines->isEmpty()) {
            throw new RuntimeException('Un devis sans ligne ne s’envoie pas.');
        }

        if ($devis->lines->contains(fn ($l): bool => (float) $l->unit_price <= 0)) {
            throw new RuntimeException('Chiffrez toutes les lignes avant d’envoyer le devis.');
        }

        return DB::transaction(function () use ($devis, $userId): StoreQuote {
            $devis->update([
                'status' => StoreQuote::STATUS_QUOTED,
                'valid_until' => $devis->valid_until ?? $this->validiteParDefaut(),
                'quoted_at' => now(),
                'handled_by' => $userId,
                'answered_at' => null,
                'decline_reason' => null,
            ]);

            if ($devis->sale_id === null && $devis->warehouse_id !== null) {
                $this->creerDevisDeGestion($devis->refresh(), $userId);
            }

            return $devis->refresh()->load('lines');
        });
    }

    /**
     * Le devis tel que la gestion le connaît : un document de type « devis »,
     * sans effet sur le stock, visible dans le web et dans le mobile.
     */
    private function creerDevisDeGestion(StoreQuote $devis, ?int $userId): Sale
    {
        $sousTotal = round((float) $devis->lines->sum('line_total'), 2);
        $taxe = app(TaxeDuLieu::class)->decomposer($sousTotal, (int) $devis->warehouse_id);

        $vente = Sale::query()->create([
            'reference' => $this->numeros->next('sale'),
            'type' => Sale::TYPE_QUOTE,
            'status' => Sale::STATUS_DRAFT,
            'customer_id' => null,
            'warehouse_id' => $devis->warehouse_id,
            'user_id' => $userId,
            'subtotal' => $sousTotal,
            'discount_percent' => 0,
            'total' => $taxe['ttc'],
            'tax_rate' => $taxe['rate'],
            'tax_amount' => $taxe['tva'],
            'note' => 'Devis boutique '.$devis->number.' — '.($devis->company ?: $devis->customer_name),
        ]);

        foreach ($devis->lines as $l) {
            $vente->lines()->create([
                'product_id' => $l->product_id,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'price_type_code' => $l->price_type_code,
                'line_total' => $l->line_total,
            ]);
        }

        $devis->update(['sale_id' => $vente->id]);

        return $vente;
    }

    /**
     * Le client accepte : la commande naît aux prix du devis, pas au barème.
     */
    public function accepter(StoreQuote $devis, string $modePaiement, ?string $adresse = null): StoreOrder
    {
        if ($devis->status !== StoreQuote::STATUS_QUOTED) {
            throw new RuntimeException('Ce devis ne peut plus être accepté.');
        }

        if ($devis->estPerime()) {
            $devis->update(['status' => StoreQuote::STATUS_EXPIRED]);

            throw new RuntimeException('Ce devis a expiré. Demandez-nous une nouvelle proposition.');
        }

        $methodes = $this->catalogue->reglages()['payment_methods'];
        if (! in_array($modePaiement, $methodes, true)) {
            throw new RuntimeException('Ce mode de paiement n’est pas proposé.');
        }

        $devis->loadMissing('lines');

        return DB::transaction(function () use ($devis, $modePaiement, $adresse): StoreOrder {
            $commande = StoreOrder::query()->create([
                'number' => $this->numeroCommande(),
                'status' => StoreOrder::STATUS_CONFIRMED,
                'customer_name' => $devis->company ?: $devis->customer_name,
                'customer_phone' => $devis->customer_phone,
                'customer_email' => $devis->customer_email,
                'city' => $devis->city,
                'address' => $adresse ?: ($devis->address ?: $devis->city),
                'note' => 'Devis '.$devis->number.($devis->note ? ' — '.$devis->note : ''),
                'payment_method' => $modePaiement,
                'payment_status' => 'unpaid',
                'subtotal' => $devis->subtotal,
                'shipping_fee' => $devis->shipping_fee,
                'total' => $devis->total,
                'paid_amount' => 0,
                'warehouse_id' => $devis->warehouse_id,
                'quote_id' => $devis->id,
                'handled_by' => $devis->handled_by,
                'confirmed_at' => now(),
            ]);

            foreach ($devis->lines as $l) {
                $commande->lines()->create([
                    'product_id' => $l->product_id,
                    'name' => $l->name,
                    'sku' => $l->sku,
                    'quantity' => $l->quantity,
                    'unit_price' => $l->unit_price,
                    'price_type_code' => $l->price_type_code,
                    'line_total' => $l->line_total,
                ]);
            }

            $devis->update(['status' => StoreQuote::STATUS_ACCEPTED, 'answered_at' => now()]);

            return $commande->load('lines');
        });
    }

    public function refuser(StoreQuote $devis, ?string $motif): StoreQuote
    {
        if ($devis->status !== StoreQuote::STATUS_QUOTED) {
            throw new RuntimeException('Ce devis ne peut plus recevoir de réponse.');
        }

        $devis->update([
            'status' => StoreQuote::STATUS_DECLINED,
            'decline_reason' => $motif,
            'answered_at' => now(),
        ]);

        return $devis->refresh();
    }

    public function annuler(StoreQuote $devis, ?string $motif, ?int $userId): StoreQuote
    {
        if ($devis->status === StoreQuote::STATUS_ACCEPTED) {
            throw new RuntimeException('Un devis accepté ne s’annule pas : annulez la commande.');
        }

        $devis->update([
            'status' => StoreQuote::STATUS_CANCELLED,
            'decline_reason' => $motif,
            'handled_by' => $userId,
            'answered_at' => now(),
        ]);

        return $devis->refresh();
    }

    /**
     * Le client retrouve son devis par le lien reçu. Un devis envoyé dont la
     * validité est passée bascule à ce moment-là : c'est la première lecture
     * qui le constate, et elle le fait une seule fois.
     */
    public function parJeton(string $jeton): ?StoreQuote
    {
        $devis = StoreQuote::query()->with('lines')->where('public_token', $jeton)->first();

        if ($devis !== null && $devis->estPerime()) {
            $devis->update(['status' => StoreQuote::STATUS_EXPIRED]);
            $devis->refresh();
        }

        return $devis;
    }

    /**
     * Vue publique : ce que le client a le droit de voir de son devis.
     *
     * @return array<string, mixed>
     */
    public function vuePublique(StoreQuote $devis): array
    {
        $devis->loadMissing('lines');
        $reglages = $this->catalogue->reglages();

        return [
            'number' => $devis->number,
            'status' => $devis->status,
            'customer_name' => $devis->customer_name,
            'company' => $devis->company,
            'city' => $devis->city,
            'note' => $devis->note,
            'reply' => $devis->reply,
            'subtotal' => $devis->subtotal,
            'shipping_fee' => $devis->shipping_fee,
            'total' => $devis->total,
            'valid_until' => $devis->valid_until?->toDateString(),
            'created_at' => $devis->created_at?->toIso8601String(),
            'quoted_at' => $devis->quoted_at?->toIso8601String(),
            'answered_at' => $devis->answered_at?->toIso8601String(),
            'payment_methods' => $reglages['payment_methods'],
            'bank_details' => $reglages['bank_details'],
            'lines' => $devis->lines->map(static fn ($l): array => [
                'name' => $l->name,
                'sku' => $l->sku,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'line_total' => $l->line_total,
            ])->values()->all(),
        ];
    }

    /**
     * Vue du tableau de bord : tout, y compris ce qui aide à chiffrer.
     *
     * @return array<string, mixed>
     */
    public function serialiser(StoreQuote $devis, bool $complet = false): array
    {
        $devis->loadMissing('lines');

        $base = [
            'id' => $devis->id,
            'number' => $devis->number,
            'status' => $devis->estPerime() ? StoreQuote::STATUS_EXPIRED : $devis->status,
            'customer_name' => $devis->customer_name,
            'customer_phone' => $devis->customer_phone,
            'customer_email' => $devis->customer_email,
            'company' => $devis->company,
            'city' => $devis->city,
            'subtotal' => $devis->subtotal,
            'shipping_fee' => $devis->shipping_fee,
            'total' => $devis->total,
            'lines_count' => $devis->lines->count(),
            'valid_until' => $devis->valid_until?->toDateString(),
            'warehouse_id' => $devis->warehouse_id,
            'warehouse' => $devis->warehouse?->name,
            'order_number' => $devis->orders->first()?->number,
            'sale_reference' => $devis->sale?->reference,
            'created_at' => $devis->created_at?->toIso8601String(),
            'quoted_at' => $devis->quoted_at?->toIso8601String(),
            'answered_at' => $devis->answered_at?->toIso8601String(),
        ];

        if (! $complet) {
            return $base;
        }

        return [
            ...$base,
            'address' => $devis->address,
            'note' => $devis->note,
            'reply' => $devis->reply,
            'decline_reason' => $devis->decline_reason,
            'public_token' => $devis->public_token,
            'handled_by' => $devis->handler?->name,
            'lines' => $devis->lines->map(static fn ($l): array => [
                'id' => $l->id,
                'product_id' => $l->product_id,
                'name' => $l->name,
                'sku' => $l->sku,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'price_type_code' => $l->price_type_code,
                'line_total' => $l->line_total,
                'stock_at_request' => $l->stock_at_request,
            ])->values()->all(),
        ];
    }

    /**
     * Ce que le tableau de bord dit des devis : ce qui attend une réponse de
     * la boutique, ce qui attend celle du client, et ce que ça rapporte.
     *
     * @return array<string, mixed>
     */
    public function tableauDeBord(): array
    {
        $parStatut = StoreQuote::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $envoyes = StoreQuote::query()->whereIn('status', [StoreQuote::STATUS_ACCEPTED, StoreQuote::STATUS_DECLINED])->count();
        $acceptes = (int) ($parStatut[StoreQuote::STATUS_ACCEPTED] ?? 0);

        return [
            'by_status' => collect(StoreQuote::STATUSES)->mapWithKeys(static fn ($s) => [$s => (int) ($parStatut[$s] ?? 0)])->all(),
            // Ce qui est en attente de notre côté : le seul chiffre sur
            // lequel la boutique peut agir tout de suite.
            'to_answer' => (int) ($parStatut[StoreQuote::STATUS_REQUESTED] ?? 0),
            'awaiting_customer' => (int) ($parStatut[StoreQuote::STATUS_QUOTED] ?? 0),
            // Valeur encore en jeu : devis envoyés, non expirés.
            'pipeline' => round((float) StoreQuote::query()
                ->where('status', StoreQuote::STATUS_QUOTED)
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
                ->sum('total'), 2),
            'accepted_value' => round((float) StoreQuote::query()->where('status', StoreQuote::STATUS_ACCEPTED)->sum('total'), 2),
            // Sur les devis auxquels le client a répondu : combien ont abouti.
            'conversion' => $envoyes > 0 ? round($acceptes * 100 / $envoyes, 1) : null,
        ];
    }

    /**
     * Numéro DEV-AAMMJJ-NNN, séquence du jour.
     */
    private function numero(): string
    {
        return $this->sequenceDuJour('DEV-', StoreQuote::query());
    }

    private function numeroCommande(): string
    {
        return $this->sequenceDuJour('WEB-', StoreOrder::query());
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $requete
     */
    private function sequenceDuJour(string $prefixe, $requete): string
    {
        $prefixe .= now()->format('ymd').'-';
        $dernier = $requete->where('number', 'like', $prefixe.'%')->orderByDesc('number')->value('number');
        $n = $dernier !== null ? ((int) substr((string) $dernier, strlen($prefixe))) + 1 : 1;

        return $prefixe.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    private function validiteParDefaut(): string
    {
        $jours = max(1, (int) StoreSetting::valeur('quote_validity_days', '7'));

        return now()->addDays($jours)->toDateString();
    }
}
