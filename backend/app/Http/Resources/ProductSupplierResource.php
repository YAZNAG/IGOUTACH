<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Supplier
 */
class ProductSupplierResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'supplier_reference' => $this->pivot?->supplier_reference,
            'last_price' => $this->pivot?->last_price,
            'lead_time_days' => $this->pivot?->lead_time_days,
        ];
    }
}
