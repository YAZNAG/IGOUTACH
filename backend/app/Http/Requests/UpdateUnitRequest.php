<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Catalog\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('unit.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');
        $unitId = $unit instanceof Unit ? $unit->id : null;

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('units', 'code')->ignore($unitId)],
            'name' => ['required', 'string', 'max:60'],
            'is_decimal' => ['boolean'],
            'position' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
