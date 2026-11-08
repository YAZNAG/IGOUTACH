<?php

declare(strict_types=1);

namespace App\Domain\Store\Services;

use App\Domain\Sales\Actions\ConfirmSaleAction;
use App\Domain\Sales\Models\Sale;
use App\Domain\Store\Models\StoreOrder;
use App\Domain\Store\Models\StorePayment;
use App\Domain\Store\Models\StoreSetting;
use App\Domain\Tax\Services\TaxeDuLieu;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Les commandes de la boutique : prise de commande côté client, puis
 * traitement côté gestion (confirmation, préparation, expédition,
 * livraison, règlements). À la livraison, une vente de gestion est créée
 * dans le lieu qui a expédié : le stock sort par le circuit habituel.
 */
final class StoreOrders
{
    public function __construct(
        private readonly StoreCatalog $catalogue,
        private readonly ConfirmSaleAction $confirmer,
        private readonly DocumentNumberGeneratorInterface $numeros,
    ) {}

    /**
     * Prise de commande. Les prix et le stock sont relus en base : le panier
     * du navigateur n'est qu'une intention.
     *
     * @param  array{customer_name: string, customer_phone: string, customer_email?: string|null, city: string, address: string, note?: string|null, payment_method: string, lines: list<array{product_id: int, quantity: int}>}  $data
     */
    public function passer(array $data, ?string $ip = null): StoreOrder
    {
        $reglages = $this->catalogue->reglages();

        if (! in_array($data['payment_method'], $reglages['payment_methods'], true)) {
            throw new RuntimeException('Ce mode de paiement n’est pas proposé.');
        }

        $ids = array_values(array_unique(array_map(fn (array $l) => (int) $l['product_id'], $data['lines'])));
        $articles = $this->catalogue->vendables()->whereIn('products.id', $ids)->get()->keyBy('id');

        $lignes = [];
        $sousTotal = 0.0;

        foreach ($data['lines'] as $l) {
            $article = $articles->get((int) $l['product_id']);
            if ($article === null) {
                throw new RuntimeException('Un article du panier n’est plus disponible.');
            }
            $qte = max(1, (int) $l['quantity']);
            if ($this->catalogue->stockDe($article) < $qte) {
                throw new RuntimeException(sprintf('Stock insuffisant pour « %s » : %d disponible(s).', $article->name, $this->catalogue->stockDe($article)));
            }
            $prix = $this->catalogue->prixDe($article);
            $lignes[] = [
                'product_id' => $article->id,
                'name' => $article->name,
                'sku' => $article->sku,
                'quantity' => $qte,
                'unit_price' => $prix,
                'line_total' => round($prix * $qte, 2),
            ];
            $sousTotal += $prix * $qte;
        }

        if ($lignes === []) {
            throw new RuntimeException('Le panier est vide.');
        }

        $sousTotal = round($sousTotal, 2);
        $port = (float) $reglages['shipping_fee'];
        if ((float) $reglages['free_shipping_from'] > 0 && $sousTotal >= (float) $reglages['free_shipping_from']) {
            $port = 0.0;
        }

        return DB::transaction(function () use ($data, $lignes, $sousTotal, $port, $ip): StoreOrder {
            $commande = StoreOrder::query()->create([
                'number' => $this->numero(),
                'status' => StoreOrder::STATUS_NEW,
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => preg_replace('/\s+/', '', $data['customer_phone']) ?? $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'city' => trim($data['city']),
                'address' => trim($data['address']),
                'note' => $data['note'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'subtotal' => $sousTotal,
                'shipping_fee' => $port,
                'total' => round($sousTotal + $port, 2),
                'paid_amount' => 0,
                'ip' => $ip,
            ]);

            foreach ($lignes as $ligne) {
                $commande->lines()->create($ligne);
            }

            return $commande->load('lines');
        });
    }

    /**
     * Numéro WEB-AAMMJJ-NNN, séquence du jour.
     */
    private function numero(): string
    {
        $prefixe = 'WEB-'.now()->format('ymd').'-';
        $dernier = StoreOrder::query()->where('number', 'like', $prefixe.'%')->orderByDesc('number')->value('number');
        $n = $dernier !== null ? ((int) substr((string) $dernier, strlen($prefixe))) + 1 : 1;

        return $prefixe.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Le client suit sa commande avec son numéro et son téléphone.
     */
    public function suivre(string $numero, string $telephone): ?StoreOrder
    {
        $tel = preg_replace('/\s+/', '', $telephone) ?? $telephone;

        return StoreOrder::query()->with('lines')
            ->where('number', strtoupper(trim($numero)))
            ->where('customer_phone', 'like', '%'.substr($tel, -9))
            ->first();
    }

    /**
     * Changement d'état par la gestion.
     *
     * @param  array{warehouse_id?: int|null, reason?: string|null}  $extra
     */
    public function changerStatut(StoreOrder $commande, string $statut, ?int $userId, array $extra = []): StoreOrder
    {
        if (! in_array($statut, StoreOrder::STATUSES, true)) {
            throw new RuntimeException('État inconnu.');
        }

        if ($commande->status === StoreOrder::STATUS_CANCELLED) {
            throw new RuntimeException('Une commande annulée ne change plus d’état.');
        }

        if ($commande->status === StoreOrder::STATUS_DELIVERED && $statut !== StoreOrder::STATUS_DELIVERED) {
            throw new RuntimeException('Une commande livrée est close : la vente de gestion existe déjà.');
        }

        return DB::transaction(function () use ($commande, $statut, $userId, $extra): StoreOrder {
            $maj = ['status' => $statut, 'handled_by' => $userId];

            if (array_key_exists('warehouse_id', $extra) && $extra['warehouse_id'] !== null) {
                $maj['warehouse_id'] = (int) $extra['warehouse_id'];
            }

            match ($statut) {
                StoreOrder::STATUS_CONFIRMED => $maj['confirmed_at'] = $commande->confirmed_at ?? now(),
                StoreOrder::STATUS_SHIPPED => $maj['shipped_at'] = now(),
                StoreOrder::STATUS_CANCELLED => [$maj['cancelled_at'], $maj['cancel_reason']] = [now(), $extra['reason'] ?? null],
                StoreOrder::STATUS_DELIVERED => $maj['delivered_at'] = now(),
                default => null,
            };

            $commande->update($maj);
            $commande->refresh();

            // La livraison fait sortir le stock : une vente de gestion est
            // creee dans le lieu qui a expedie, une seule fois.
            if ($statut === StoreOrder::STATUS_DELIVERED && $commande->sale_id === null) {
                $this->facturer($commande, $userId);
            }

            return $commande->refresh();
        });
    }

    /**
     * La vente de gestion qui matérialise la livraison : client de passage,
     * prix de la commande, lieu d'expédition. Le stock sort par la
     * confirmation habituelle, avec ses contrôles.
     */
    public function facturer(StoreOrder $commande, ?int $userId): Sale
    {
        if ($commande->warehouse_id === null) {
            throw new RuntimeException('Indiquez d’abord le lieu qui a expédié la commande.');
        }

        $commande->loadMissing('lines');
        $sousTotal = round((float) $commande->lines->sum('line_total'), 2);
        $taxe = app(TaxeDuLieu::class)->decomposer($sousTotal, (int) $commande->warehouse_id);

        $vente = Sale::query()->create([
            'reference' => $this->numeros->next('sale'),
            'type' => Sale::TYPE_INVOICE,
            'status' => Sale::STATUS_DRAFT,
            'customer_id' => null,
            'warehouse_id' => $commande->warehouse_id,
            'user_id' => $userId,
            'subtotal' => $sousTotal,
            'discount_percent' => 0,
            'total' => $taxe['ttc'],
            'tax_rate' => $taxe['rate'],
            'tax_amount' => $taxe['tva'],
            'note' => 'Commande en ligne '.$commande->number.' — '.$commande->customer_name,
        ]);

        foreach ($commande->lines as $l) {
            $vente->lines()->create([
                'product_id' => $l->product_id,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'price_type_code' => 'detail',
                'line_total' => $l->line_total,
            ]);
        }

        $vente = $this->confirmer->execute($vente, $userId);

        $commande->update(['sale_id' => $vente->id]);

        return $vente;
    }

    /**
     * Règlement reçu (à la livraison, par virement…).
     */
    public function encaisser(StoreOrder $commande, string $methode, float $montant, ?string $reference, string $date, ?string $note, ?int $userId): StorePayment
    {
        if ($montant <= 0) {
            throw new RuntimeException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($commande, $methode, $montant, $reference, $date, $note, $userId): StorePayment {
            $paiement = $commande->payments()->create([
                'method' => $methode,
                'amount' => round($montant, 2),
                'reference' => $reference,
                'received_at' => $date,
                'note' => $note,
                'received_by' => $userId,
            ]);

            $this->recalculerPaiement($commande);

            return $paiement;
        });
    }

    public function annulerPaiement(StoreOrder $commande, StorePayment $paiement): void
    {
        DB::transaction(function () use ($commande, $paiement): void {
            $paiement->delete();
            $this->recalculerPaiement($commande);
        });
    }

    private function recalculerPaiement(StoreOrder $commande): void
    {
        $paye = round((float) $commande->payments()->sum('amount'), 2);
        $commande->update([
            'paid_amount' => $paye,
            'payment_status' => $paye <= 0 ? 'unpaid' : ($paye + 0.001 >= (float) $commande->total ? 'paid' : 'partial'),
        ]);
    }

    /**
     * Les chiffres du tableau de bord de la boutique.
     *
     * @return array<string, mixed>
     */
    public function tableauDeBord(): array
    {
        $parStatut = StoreOrder::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $livrees = fn ($q) => $q->where('status', StoreOrder::STATUS_DELIVERED);

        return [
            'by_status' => collect(StoreOrder::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($parStatut[$s] ?? 0)])->all(),
            'today' => [
                'orders' => StoreOrder::query()->whereDate('created_at', today())->count(),
                'revenue' => round((float) StoreOrder::query()->whereDate('created_at', today())->where('status', '!=', StoreOrder::STATUS_CANCELLED)->sum('total'), 2),
            ],
            'month' => [
                'orders' => StoreOrder::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', '!=', StoreOrder::STATUS_CANCELLED)->count(),
                'revenue' => round((float) $livrees(StoreOrder::query())->whereMonth('delivered_at', now()->month)->whereYear('delivered_at', now()->year)->sum('total'), 2),
                'unpaid' => round((float) StoreOrder::query()->whereNotIn('status', [StoreOrder::STATUS_CANCELLED])->whereIn('payment_status', ['unpaid', 'partial'])->selectRaw('COALESCE(SUM(total - paid_amount), 0) as d')->value('d'), 2),
            ],
            'top_products' => DB::table('store_order_lines as l')
                ->join('store_orders as o', 'o.id', '=', 'l.order_id')
                ->where('o.status', '!=', StoreOrder::STATUS_CANCELLED)
                ->where('o.created_at', '>=', now()->subDays(30))
                ->groupBy('l.product_id', 'l.name')
                ->selectRaw('l.product_id, l.name, SUM(l.quantity) as qty, SUM(l.line_total) as revenue')
                ->orderByDesc('qty')->limit(8)->get()
                ->map(fn ($r) => ['product_id' => (int) $r->product_id, 'name' => $r->name, 'qty' => (int) $r->qty, 'revenue' => round((float) $r->revenue, 2)])->all(),
            'catalog' => [
                'online' => $this->catalogue->vendables()->count(),
                'without_image' => $this->catalogue->vendables()->whereDoesntHave('images')->count(),
                'out_of_stock' => $this->catalogue->vendables()->whereRaw('(SELECT COALESCE(SUM(s.quantity), 0) FROM stocks s WHERE s.product_id = products.id) <= 0')->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialiser(StoreOrder $c, bool $complet = false): array
    {
        $base = [
            'id' => $c->id,
            'number' => $c->number,
            'status' => $c->status,
            'customer_name' => $c->customer_name,
            'customer_phone' => $c->customer_phone,
            'city' => $c->city,
            'payment_method' => $c->payment_method,
            'payment_status' => $c->payment_status,
            'subtotal' => $c->subtotal,
            'shipping_fee' => $c->shipping_fee,
            'total' => $c->total,
            'paid_amount' => $c->paid_amount,
            'lines_count' => $c->lines_count ?? $c->lines->count(),
            'warehouse' => $c->warehouse?->code,
            'warehouse_id' => $c->warehouse_id,
            'sale_reference' => $c->sale?->reference,
            'created_at' => $c->created_at?->toIso8601String(),
        ];

        if (! $complet) {
            return $base;
        }

        return [
            ...$base,
            'customer_email' => $c->customer_email,
            'address' => $c->address,
            'note' => $c->note,
            'cancel_reason' => $c->cancel_reason,
            'handled_by' => $c->handler?->name,
            'confirmed_at' => $c->confirmed_at?->toIso8601String(),
            'shipped_at' => $c->shipped_at?->toIso8601String(),
            'delivered_at' => $c->delivered_at?->toIso8601String(),
            'cancelled_at' => $c->cancelled_at?->toIso8601String(),
            'lines' => $c->lines->map(fn ($l): array => [
                'id' => $l->id, 'product_id' => $l->product_id, 'sku' => $l->sku, 'name' => $l->name,
                'quantity' => $l->quantity, 'unit_price' => $l->unit_price, 'line_total' => $l->line_total,
            ])->values()->all(),
            'payments' => $c->payments->map(fn ($p): array => [
                'id' => $p->id, 'method' => $p->method, 'amount' => $p->amount, 'reference' => $p->reference,
                'received_at' => $p->received_at?->toDateString(), 'note' => $p->note, 'received_by' => $p->receiver?->name,
            ])->values()->all(),
        ];
    }

    /**
     * Suivi public : moins de détails, pas de données internes.
     *
     * @return array<string, mixed>
     */
    public function suivi(StoreOrder $c): array
    {
        return [
            'number' => $c->number,
            'status' => $c->status,
            'payment_method' => $c->payment_method,
            'payment_status' => $c->payment_status,
            'subtotal' => $c->subtotal,
            'shipping_fee' => $c->shipping_fee,
            'total' => $c->total,
            'city' => $c->city,
            'created_at' => $c->created_at?->toIso8601String(),
            'shipped_at' => $c->shipped_at?->toIso8601String(),
            'delivered_at' => $c->delivered_at?->toIso8601String(),
            'lines' => $c->lines->map(fn ($l): array => ['name' => $l->name, 'quantity' => $l->quantity, 'unit_price' => $l->unit_price, 'line_total' => $l->line_total])->values()->all(),
            'bank_details' => $c->payment_method === StoreOrder::PAYMENT_TRANSFER ? StoreSetting::valeur('bank_details') : null,
        ];
    }
}
