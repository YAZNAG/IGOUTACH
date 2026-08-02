<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Annule une vente confirmée : retour de la marchandise au stock
 * (mouvement return_in, jamais d'UPDATE des mouvements existants)
 * et contre-passation de la créance.
 */
final class CancelSaleAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
        private readonly CustomerLedger $ledger,
    ) {}

    public function execute(Sale $sale, ?int $userId = null): Sale
    {
        if ($sale->status === Sale::STATUS_CANCELLED) {
            throw new RuntimeException('Document déjà annulé.');
        }

        return DB::transaction(function () use ($sale, $userId): Sale {
            if ($sale->status === Sale::STATUS_CONFIRMED && $sale->type === Sale::TYPE_INVOICE) {
                foreach ($sale->lines as $line) {
                    $this->stock->increase(new StockMovementData(
                        warehouseId: $sale->warehouse_id,
                        productId: $line->product_id,
                        quantity: $line->quantity,
                        movementTypeCode: 'return_in',
                        referenceType: Sale::class,
                        referenceId: $sale->id,
                        userId: $userId,
                        note: 'Annulation '.$sale->reference,
                    ));
                }

                $this->ledger->record(
                    customerId: $sale->customer_id,
                    type: CustomerLedgerEntry::TYPE_RETURN,
                    amount: -(float) $sale->total,
                    referenceType: Sale::class,
                    referenceId: $sale->id,
                    note: 'Annulation '.$sale->reference,
                    userId: $userId,
                );
            }

            $sale->update(['status' => Sale::STATUS_CANCELLED]);

            return $sale->refresh();
        });
    }
}
