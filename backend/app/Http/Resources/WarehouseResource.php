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
            'created_at' => $this->created_at,
        ];
    }
}
