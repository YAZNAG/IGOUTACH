<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBrandRequest extends FormRequest
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
        return [
            'code' => ['nullable', 'string', 'max:20', 'unique:brands,code'],
            'name' => ['required', 'string', 'max:120', 'unique:brands,name'],
            'website' => ['nullable', 'url', 'max:255'],
            'position' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
