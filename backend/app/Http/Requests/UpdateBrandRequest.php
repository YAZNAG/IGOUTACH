<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Catalog\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('brand.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $brand = $this->route('brand');
        $brandId = $brand instanceof Brand ? $brand->id : null;

        return [
            'code' => ['nullable', 'string', 'max:20', Rule::unique('brands', 'code')->ignore($brandId)],
            'name' => ['required', 'string', 'max:120', Rule::unique('brands', 'name')->ignore($brandId)],
            'website' => ['nullable', 'url', 'max:255'],
            'position' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
