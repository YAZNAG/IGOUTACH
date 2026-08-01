<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Models\Inventory;
use App\Domain\Stock\Models\InventoryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Valide un inventaire : régularise le stock de chaque article selon l'écart
 * (compté − théorique) via un mouvement d'ajustement daté.
 */
final class ApproveInventoryAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
    ) {}

    public function execute(Inventory $inventory, ?int $userId = null): Inventory
    {
        if ($inventory->status !== Inventory::STATUS_DRAFT) {
            throw new RuntimeException('Seul un inventaire en brouillon peut être validé.');
        }

        $date = $inventory->counted_at !== null
            ? $inventory->counted_at->format('Y-m-d').' 12:00:00'
            : null;

        return DB::transaction(function () use ($inventory, $userId, $date): Inventory {
            foreach ($inventory->lines()->where('difference', '!=', 0)->get() as $line) {
                $this->regularize($inventory, $line, $userId, $date);
            }

            $inventory->update([
                'status' => Inventory::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            return $inventory->refresh();
        });
    }

    private function regularize(Inventory $inventory, InventoryLine $line, ?int $userId, ?string $date): void
    {
        $diff = $line->difference;
        $note = 'Régularisation inventaire '.$inventory->reference;

        $data = new StockMovementData(
            warehouseId: $inventory->warehouse_id,
            productId: $line->product_id,
            quantity: abs($diff),
            movementTypeCode: 'adjustment',
            referenceType: 'inventory',
            referenceId: $inventory->id,
            userId: $userId,
            note: $note,
            occurredAt: $date,
        );

        if ($diff > 0) {
            $this->stock->increase($data);
        } else {
            $this->stock->decrease($data);
        }
    }
}
