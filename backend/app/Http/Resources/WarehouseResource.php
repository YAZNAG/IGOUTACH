<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Warehouse
 */
class WarehouseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'warehouse_type_id' => $this->warehouse_type_id,
            'type' => WarehouseTypeResource::make($this->whenLoaded('type')),
            'manager_id' => $this->manager_id,
            'parent_id' => $this->parent_id,
            'address' => $this->address,
            'city' => $this->city,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            // TVA du lieu : l'écran de vente annonce HT, TVA et TTC dès qu'elle
            // n'est pas nulle, et ne change rien ailleurs.
            'vat_rate' => round((float) $this->vat_rate, 2),
            'prices_include_vat' => (bool) $this->prices_include_vat,
            'created_at' => $this->created_at,
        ];
    }
}
