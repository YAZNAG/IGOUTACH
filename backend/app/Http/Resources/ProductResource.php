<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'unit_id' => $this->unit_id,
            'sale_price' => $this->sale_price,
            'tax_rate' => $this->tax_rate,
            'is_serialized' => $this->is_serialized,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
            // Le prix d'achat n'est exposé qu'avec la permission dédiée.
            'cost_price' => $this->when(
                $request->user()?->can('product.view_cost_price') ?? false,
                fn () => $this->cost_price,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
