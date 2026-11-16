<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use App\Domain\Sales\Models\SaleReturn;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Retour client rattaché à la vente d'origine.
 *
 * Le même article se vend à des prix différents selon le client et le jour :
 * sans savoir quelle vente revient, on ne sait ni combien rembourser, ni de
 * combien réduire le bénéfice. Le retour reprend donc la ligne de vente :
 * son prix, son client, son lieu.
 */
final class RetourClient
{
    public function __construct(
        private readonly StockWriterInterface $stock,
        private readonly CustomerLedger $ledger,
    ) {}

    /**
     * Les ventes confirmées qui contiennent l'article, de la plus récente à
     * la plus ancienne, avec ce qu'il reste à pouvoir retourner sur chacune.
     *
     * @return list<array<string, mixed>>
     */
    public function ventesAvecArticle(int $productId, int $lieu = 0, int $limite = 50): array
    {
        $lignes = SaleLine::query()
            ->with(['sale.customer:id,code,name', 'sale.warehouse:id,code,name'])
            ->where('product_id', $productId)
            ->whereHas('sale', fn ($q) => $q
                ->where('type', Sale::TYPE_INVOICE)
                ->where('status', Sale::STATUS_CONFIRMED)
                ->when($lieu > 0, fn ($w) => $w->where('warehouse_id', $lieu)))
            ->get();

        $rendus = SaleReturn::query()
            ->whereIn('sale_line_id', $lignes->pluck('id'))
            ->groupBy('sale_line_id')
            ->pluck(DB::raw('SUM(quantity) as q'), 'sale_line_id');

        return $lignes
            ->sortByDesc(fn (SaleLine $l) => ($l->sale?->confirmed_at?->timestamp ?? 0) * 100000 + $l->sale_id)
            ->take($limite)
            ->values()
            ->map(function (SaleLine $l) use ($rendus): array {
                $deja = (int) ($rendus[$l->id] ?? 0);

                return [
                    'sale_id' => $l->sale_id,
                    'sale_line_id' => $l->id,
                    'reference' => $l->sale?->reference,
                    'date' => $l->sale?->confirmed_at?->format('Y-m-d H:i'),
                    'customer' => $l->sale?->customer?->name ?? 'Client de passage',
                    'customer_code' => $l->sale?->customer?->code,
                    'warehouse_id' => $l->sale?->warehouse_id,
                    'warehouse' => $l->sale?->warehouse?->code,
                    'quantity' => (int) $l->quantity,
                    'unit_price' => (float) $l->unit_price,
                    'already_returned' => $deja,
                    'returnable' => max(0, (int) $l->quantity - $deja),
                    'payment_status' => $l->sale?->payment_status,
                ];
            })
            ->all();
    }

    /**
     * Enregistre un retour sur une ligne de vente : stock, avoir, trace.
     *
     * - Le stock revient dans le lieu de la vente (sortie SAV immédiate si
     *   l'article est défectueux : il ne se revend pas).
     * - Ce que la facture devait encore est réduit d'autant (avoir imputé) ;
     *   le reste, s'il y en a, est rendu au client de la main à la main.
     */
    public function enregistrer(
        int $saleLineId,
        int $quantite,
        string $condition,
        ?string $note,
        ?string $occurredAt,
        ?int $userId,
    ): SaleReturn {
        return DB::transaction(function () use ($saleLineId, $quantite, $condition, $note, $occurredAt, $userId): SaleReturn {
            /** @var SaleLine $ligne */
            $ligne = SaleLine::query()->with('sale')->lockForUpdate()->findOrFail($saleLineId);
            /** @var Sale|null $vente */
            $vente = $ligne->sale;

            if ($vente === null || $vente->type !== Sale::TYPE_INVOICE || $vente->status !== Sale::STATUS_CONFIRMED) {
                throw new RuntimeException('Un retour ne se rattache qu’à une facture confirmée.');
            }

            $deja = (int) SaleReturn::query()->where('sale_line_id', $ligne->id)->sum('quantity');
            $restant = (int) $ligne->quantity - $deja;

            if ($quantite < 1 || $quantite > $restant) {
                throw new RuntimeException(
                    "Sur la vente {$vente->reference}, il reste au plus {$restant} unité(s) à retourner pour cet article."
                );
            }

            $prix = round((float) $ligne->unit_price, 2);
            $montant = round($prix * $quantite, 2);

            // L'avoir s'impute d'abord sur ce que la facture doit encore : un
            // client qui n'a pas payé ne se voit pas rembourser en espèces.
            $du = round((float) $vente->total - (float) $vente->paid_amount, 2);
            $impute = round(min($montant, max($du, 0)), 2);

            $libelle = ($condition === SaleReturn::CONDITION_RESELLABLE ? 'Retour client revendable' : 'Retour client défectueux')
                ." — {$vente->reference}"
                .($vente->customer?->name ? " ({$vente->customer->name})" : '')
                .($note !== null && $note !== '' ? " — {$note}" : '');

            $retour = SaleReturn::query()->create([
                'sale_id' => $vente->id,
                'sale_line_id' => $ligne->id,
                'product_id' => $ligne->product_id,
                'warehouse_id' => $vente->warehouse_id,
                'customer_id' => $vente->customer_id,
                'quantity' => $quantite,
                'unit_price' => $prix,
                'amount' => $montant,
                'credited_amount' => $impute,
                'condition' => $condition,
                'note' => $note,
                'occurred_at' => $occurredAt ?? now(),
                'created_by' => $userId,
            ]);

            $this->stock->increase(new StockMovementData(
                warehouseId: (int) $vente->warehouse_id,
                productId: (int) $ligne->product_id,
                quantity: $quantite,
                movementTypeCode: 'return_in',
                referenceType: 'customer_return',
                referenceId: $retour->id,
                userId: $userId,
                note: $libelle,
                occurredAt: $occurredAt,
            ));

            if ($condition === SaleReturn::CONDITION_DEFECTIVE) {
                $this->stock->decrease(new StockMovementData(
                    warehouseId: (int) $vente->warehouse_id,
                    productId: (int) $ligne->product_id,
                    quantity: $quantite,
                    movementTypeCode: 'out',
                    referenceType: 'customer_return_defective',
                    referenceId: $retour->id,
                    userId: $userId,
                    note: 'Sortie SAV — '.$libelle,
                    occurredAt: $occurredAt,
                ));
            }

            if ($impute > 0) {
                $paye = round((float) $vente->paid_amount + $impute, 2);
                $vente->update([
                    'paid_amount' => $paye,
                    'payment_status' => $paye >= (float) $vente->total ? 'paid' : 'partial',
                ]);

                if ($vente->customer_id !== null) {
                    $this->ledger->record(
                        (int) $vente->customer_id,
                        'refund',
                        -$impute,
                        SaleReturn::class,
                        $retour->id,
                        "Avoir sur {$vente->reference} — retour de {$quantite} × {$ligne->product?->name}",
                        $userId,
                    );
                }
            }

            return $retour;
        });
    }
}
