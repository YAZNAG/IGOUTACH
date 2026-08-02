<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Stock
 */
class StockResource extends JsonResource
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
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'average_cost' => $this->average_cost,
            'warehouse' => $this->whenLoaded('warehouse', function () {
                return [
                    'id' => $this->warehouse->id,
                    'name' => $this->warehouse->name,
                ];
            }),
        ];
    }
}
