<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Bon de sortie : sortie manuelle de stock (casse, perte, usage interne, SAV).
 * Toutes les lignes sont appliquées dans une seule transaction (tout ou rien).
 */
final class IssueStockAction
{
    public const REASONS = [
        'breakage' => 'Casse',
        'loss' => 'Perte',
        'internal_use' => 'Usage interne',
        'sav' => 'SAV',
    ];

    public function __construct(
        private readonly StockWriterInterface $stock,
    ) {}

    /**
     * @param  list<array{product_id: int, quantity: int, note?: string|null}>  $lines
     * @return list<StockMovement>
     */
    public function execute(int $warehouseId, string $reasonCode, array $lines, ?int $userId = null): array
    {
        $label = self::REASONS[$reasonCode] ?? $reasonCode;

        return DB::transaction(function () use ($warehouseId, $label, $lines, $userId): array {
            $movements = [];

            foreach ($lines as $line) {
                $note = 'Bon de sortie ('.$label.')';
                if (! empty($line['note'])) {
                    $note .= ' — '.$line['note'];
                }

                $movements[] = $this->stock->decrease(new StockMovementData(
                    warehouseId: $warehouseId,
                    productId: $line['product_id'],
                    quantity: $line['quantity'],
                    movementTypeCode: 'out',
                    referenceType: 'stock_issue',
                    userId: $userId,
                    note: $note,
                ));
            }

            return $movements;
        });
    }
}
