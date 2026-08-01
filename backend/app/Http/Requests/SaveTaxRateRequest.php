<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Catalog\Models\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tax_rate.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $taxRate = $this->route('taxRate');
        $id = $taxRate instanceof TaxRate ? $taxRate->id : null;

        return [
            'rate' => ['required', 'numeric', 'min:0', 'max:100', Rule::unique('tax_rates', 'rate')->ignore($id)],
            'label' => ['required', 'string', 'max:60'],
            'is_default' => ['boolean'],
            'position' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
