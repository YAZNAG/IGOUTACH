<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Bon d'entrée : entrée manuelle de stock dans un lieu (avec date et coût
 * unitaire pour le calcul du CMUP). Toutes les lignes en une transaction.
 */
final class EntryStockAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
    ) {}

    /**
     * @param  list<array{product_id: int, quantity: int, unit_cost?: float|int|null, note?: string|null}>  $lines
     * @return list<StockMovement>
     */
    public function execute(int $warehouseId, string $date, array $lines, ?int $userId = null): array
    {
        return DB::transaction(function () use ($warehouseId, $date, $lines, $userId): array {
            $movements = [];

            foreach ($lines as $line) {
                $movements[] = $this->stock->increase(new StockMovementData(
                    warehouseId: $warehouseId,
                    productId: $line['product_id'],
                    quantity: $line['quantity'],
                    movementTypeCode: 'in',
                    unitCost: (float) ($line['unit_cost'] ?? 0),
                    referenceType: 'stock_entry',
                    userId: $userId,
                    note: ! empty($line['note']) ? $line['note'] : "Bon d'entrée",
                    occurredAt: $date.' 12:00:00',
                ));
            }

            return $movements;
        });
    }
}
