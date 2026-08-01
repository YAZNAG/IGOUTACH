<?php

declare(strict_types=1);

namespace App\Domain\Stock\Repositories;

use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Contracts\StockValuationInterface;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferLine;
use App\Domain\Stock\Models\TransferStatus;
use Illuminate\Support\Facades\DB;

final class StockRepository implements StockReaderInterface, StockWriterInterface
{
    public function __construct(
        private readonly StockValuationInterface $valuation,
    ) {}

    public function quantityFor(int $warehouseId, int $productId): int
    {
        return (int) (Stock::withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0);
    }

    public function globalQuantityFor(int $productId): int
    {
        $inStock = (int) Stock::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->sum('quantity');

        // La marchandise en transit n'appartient à aucun lieu mais reste
        // comptée dans le stock global (brief §8).
        $inTransitStatusId = TransferStatus::where('code', TransferStatus::IN_TRANSIT)->value('id');

        $inTransitTransferIds = Transfer::where('transfer_status_id', $inTransitStatusId)->pluck('id');

        $inTransit = (int) TransferLine::whereIn('transfer_id', $inTransitTransferIds)
            ->where('product_id', $productId)
            ->sum('quantity_sent');

        return $inStock + $inTransit;
    }

    public function increase(StockMovementData $data): StockMovement
    {
        return DB::transaction(function () use ($data): StockMovement {
            $stock = $this->lockOrCreateStock($data->warehouseId, $data->productId);
            $type = $this->movementType($data->movementTypeCode);

            $newCost = $type->affects_valuation
                ? $this->valuation->newUnitCost(
                    $stock->quantity,
                    (float) $stock->average_cost,
                    $data->quantity,
                    $data->unitCost,
                )
                : (float) $stock->average_cost;

            $stock->quantity += $data->quantity;
            $stock->average_cost = (string) $newCost;
            $stock->save();

            return $this->recordMovement($data, $type, $data->quantity, $stock->quantity, $newCost);
        });
    }

    public function decrease(StockMovementData $data): StockMovement
    {
        return DB::transaction(function () use ($data): StockMovement {
            $stock = $this->lockOrCreateStock($data->warehouseId, $data->productId);
            $type = $this->movementType($data->movementTypeCode);

            if ($stock->quantity < $data->quantity) {
                throw InsufficientStockException::for(
                    $data->warehouseId,
                    $data->productId,
                    $stock->quantity,
                    $data->quantity,
                );
            }

            $unitCost = (float) $stock->average_cost;
            $stock->quantity -= $data->quantity;
            $stock->save();

            return $this->recordMovement($data, $type, -$data->quantity, $stock->quantity, $unitCost);
        });
    }

    private function lockOrCreateStock(int $warehouseId, int $productId): Stock
    {
        $stock = Stock::withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock !== null) {
            return $stock;
        }

        Stock::withoutGlobalScopes()->create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'average_cost' => '0',
        ]);

        /** @var Stock $locked */
        $locked = Stock::withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    private function recordMovement(
        StockMovementData $data,
        MovementType $type,
        int $signedQuantity,
        int $balanceAfter,
        float $unitCost,
    ): StockMovement {
        $attributes = [
            'warehouse_id' => $data->warehouseId,
            'product_id' => $data->productId,
            'movement_type_id' => $type->id,
            'quantity' => $signedQuantity,
            'unit_cost' => (string) round($unitCost, 2),
            'balance_after' => $balanceAfter,
            'reference_type' => $data->referenceType,
            'reference_id' => $data->referenceId,
            'user_id' => $data->userId,
            'note' => $data->note,
        ];

        // Date choisie (document) si fournie, sinon horodatage courant.
        if ($data->occurredAt !== null) {
            $attributes['created_at'] = $data->occurredAt;
        }

        return StockMovement::withoutGlobalScopes()->create($attributes);
    }

    private function movementType(string $code): MovementType
    {
        return MovementType::where('code', $code)->firstOrFail();
    }
}
