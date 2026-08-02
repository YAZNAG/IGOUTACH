<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:191'],
            'is_company' => ['boolean'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'ice' => ['nullable', 'string', 'max:30'],
            'price_type_id' => ['nullable', 'integer', 'exists:price_types,id'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
