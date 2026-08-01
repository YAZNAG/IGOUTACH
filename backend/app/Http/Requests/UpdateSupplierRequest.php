<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('supplier.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $id = $supplier instanceof Supplier ? $supplier->id : null;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('suppliers', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:191'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'ice' => ['nullable', 'string', 'max:30'],
            'rc' => ['nullable', 'string', 'max:30'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
