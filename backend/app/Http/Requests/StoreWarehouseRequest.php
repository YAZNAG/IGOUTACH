<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisation gérée par le middleware `can:warehouse.create`.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:191'],
            'warehouse_type_id' => ['required', 'integer', 'exists:warehouse_types,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'address' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            // TVA du lieu : 0 ailleurs, 20 pour un depot qui facture avec TVA.
            'vat_rate' => ['sometimes', 'numeric', 'between:0,100'],
            // Prix saisis tout compris : la TVA s'extrait au lieu de s'ajouter.
            'prices_include_vat' => ['sometimes', 'boolean'],
        ];
    }
}
