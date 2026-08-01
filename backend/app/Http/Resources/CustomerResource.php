<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Customers\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
final class CustomerResource extends JsonResource
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
            'is_company' => $this->is_company,
            'contact_name' => $this->contact_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'ice' => $this->ice,
            'credit_limit' => (float) $this->credit_limit,
            'balance' => (float) $this->balance,
            'available_credit' => $this->availableCredit(),
            'is_blocked' => $this->is_blocked,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];
    }
}
