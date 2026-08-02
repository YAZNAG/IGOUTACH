<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveInventoryLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lines' => ['present', 'array'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.counted_quantity' => ['required', 'integer', 'min:0'],
            'lines.*.reason' => ['nullable', 'string', 'max:191'],
        ];
    }
}
