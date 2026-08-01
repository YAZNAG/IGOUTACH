<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\Models\Inventory;
use App\Domain\Stock\Models\InventoryLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Inventory
 */
final class InventoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse !== null ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),
            'counted_at' => $this->counted_at?->format('Y-m-d'),
            'status' => $this->status,
            'note' => $this->note,
            'lines_count' => $this->whenCounted('lines'),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn (InventoryLine $line): array => [
                'product_id' => $line->product_id,
                'sku' => $line->product?->sku,
                'name' => $line->product?->name,
                'system_quantity' => $line->system_quantity,
                'counted_quantity' => $line->counted_quantity,
                'difference' => $line->difference,
            ])->all()),
        ];
    }
}
