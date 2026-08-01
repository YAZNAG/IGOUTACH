<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Pricing\Models\PriceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisation gérée par le middleware `can:price.manage`.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.price_type_code' => [
                'required',
                'string',
                Rule::in([PriceType::DETAIL, PriceType::SEMI_GROS, PriceType::GROS]),
            ],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.min_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prices.*.min_quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
