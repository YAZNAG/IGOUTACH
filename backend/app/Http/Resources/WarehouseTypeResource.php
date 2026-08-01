<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Warehouses\Models\WarehouseType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WarehouseType
 */
class WarehouseTypeResource extends JsonResource
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
            'allows_sales' => $this->allows_sales,
            'allows_purchase_receipt' => $this->allows_purchase_receipt,
            'requires_transfer_approval' => $this->requires_transfer_approval,
        ];
    }
}
