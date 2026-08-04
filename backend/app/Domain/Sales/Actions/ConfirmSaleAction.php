<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Confirme une vente : contrôle du crédit client, sortie de stock
 * (facture uniquement) et créance au grand-livre.
 */
final class ConfirmSaleAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
        private readonly CustomerLedger $ledger,
    ) {}

    public function execute(Sale $sale, ?int $userId = null, bool $allowOverCredit = false): Sale
    {
        if ($sale->status !== Sale::STATUS_DRAFT) {
            throw new RuntimeException('Seul un document en brouillon peut être confirmé.');
        }

        if ($sale->lines()->count() === 0) {
            throw new RuntimeException('Le document ne contient aucune ligne.');
        }

        return DB::transaction(function () use ($sale, $userId, $allowOverCredit): Sale {
            // Client de passage : aucune fiche, aucun crédit possible.
            $customer = $sale->customer_id !== null
                ? Customer::query()->lockForUpdate()->findOrFail($sale->customer_id)
                : null;

            if ($sale->type === Sale::TYPE_INVOICE) {
                if ($customer !== null) {
                    if ($customer->is_blocked) {
                        throw new RuntimeException('Client bloqué : encours au-dessus du plafond. Déblocage requis.');
                    }

                    $limit = (float) $customer->credit_limit;
                    $projected = (float) $customer->balance + (float) $sale->total;
                    if ($limit > 0 && $projected > $limit && ! $allowOverCredit) {
                        throw new RuntimeException(sprintf(
                            'Plafond de crédit dépassé (encours projeté %.2f DH > plafond %.2f DH).',
                            $projected,
                            $limit,
                        ));
                    }
                }

                foreach ($sale->lines as $line) {
                    $this->stock->decrease(new StockMovementData(
                        warehouseId: $sale->warehouse_id,
                        productId: $line->product_id,
                        quantity: $line->quantity,
                        movementTypeCode: 'out',
                        referenceType: Sale::class,
                        referenceId: $sale->id,
                        userId: $userId,
                        note: 'Vente '.$sale->reference,
                    ));
                }

                if ($customer !== null) {
                    $this->ledger->record(
                        customerId: $customer->id,
                        type: CustomerLedgerEntry::TYPE_INVOICE,
                        amount: (float) $sale->total,
                        referenceType: Sale::class,
                        referenceId: $sale->id,
                        note: 'Facture '.$sale->reference,
                        userId: $userId,
                    );
                }
            }

            $updates = ['status' => Sale::STATUS_CONFIRMED, 'confirmed_at' => now()];

            // Sans fiche client, pas de crédit possible : la vente est
            // considérée payée comptant dans son intégralité.
            if ($customer === null && $sale->type === Sale::TYPE_INVOICE) {
                $updates['paid_amount'] = $sale->total;
                $updates['payment_status'] = 'paid';
            }

            $sale->update($updates);

            return $sale->refresh()->load(['lines.product:id,sku,name', 'customer:id,code,name']);
        });
    }
}
