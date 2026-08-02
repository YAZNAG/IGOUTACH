<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Purchasing\Models\PurchaseOrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrderLine
 */
final class PurchaseOrderLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'product' => [
                'id' => $product?->id,
                'sku' => $product?->sku,
                'name' => $product?->name,
                // Stock actuel (si permission product.view_cost_price)
                'current_stock' => $product?->id ? $this->getCurrentStock($product->id) : null,
                // Seuil minimal
                'min_stock' => $product?->min_stock,
            ],
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'remaining' => $this->remaining(),
            'position' => $this->position,
            // Dernier prix connu (si permission product.view_cost_price)
            'last_price_known' => $request->user()?->can('product.view_cost_price') ? $product?->cost_price : null,
        ];
    }

    private function getCurrentStock(int $productId): int
    {
        return (int) \DB::table('stocks')
            ->where('product_id', $productId)
            ->sum('quantity');
    }
}
