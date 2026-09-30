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
            // Le coût d'achat de l'article : un seul coût dans toute
            // l'application, celui du dernier achat.
            'average_cost' => $this->whenLoaded(
                'product',
                fn () => number_format((float) $this->product->cost_price, 2, '.', ''),
                $this->average_cost,
            ),
            'warehouse' => $this->whenLoaded('warehouse', function () {
                return [
                    'id' => $this->warehouse->id,
                    'name' => $this->warehouse->name,
                ];
            }),
        ];
    }
}
