<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'movement_type_id' => $this->movement_type_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'balance_after' => $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'movement_type' => $this->whenLoaded('movementType', function () {
                return [
                    'id' => $this->movementType->id,
                    'code' => $this->movementType->code,
                    'name' => $this->movementType->name,
                ];
            }),
        ];
    }
}
