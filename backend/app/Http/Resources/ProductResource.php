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
            // Stock du lieu demandé (présent seulement si warehouse_id est passé à l'index).
            'current_stock' => $this->when(
                array_key_exists('current_stock', $this->resource->getAttributes()),
                fn () => (int) $this->resource->getAttributes()['current_stock'],
            ),
            // Le prix d'achat n'est exposé qu'avec la permission dédiée.
            'cost_price' => $this->when(
                $request->user()?->can('product.view_cost_price') ?? false,
                fn () => $this->cost_price,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Relations enrichies (utilisées dans la vue détail)
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return $this->brand ? [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ] : null;
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'code' => $this->unit->code,
                ];
            }),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn ($image) => [
                    'id' => $image->id,
                    'path' => $image->path,
                    'is_main' => $image->is_main,
                    'position' => $image->position,
                ])->all();
            }),
            'attributes' => $this->whenLoaded('attributes', function () {
                return $this->attributes->map(fn ($attr) => [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'value' => $attr->value,
                    'position' => $attr->position,
                ])->all();
            }),
        ];
    }
}
