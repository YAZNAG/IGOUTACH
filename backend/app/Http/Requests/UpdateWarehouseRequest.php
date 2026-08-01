<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisation gérée par le middleware `can:warehouse.update`.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $warehouse = $this->route('warehouse');
        $warehouseId = $warehouse instanceof Warehouse ? $warehouse->getKey() : null;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
            'name' => ['required', 'string', 'max:191'],
            'warehouse_type_id' => ['required', 'integer', 'exists:warehouse_types,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'different:'.($warehouseId ?? 0), 'exists:warehouses,id'],
            'address' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }
}
