<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Catalog\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisation gérée par le middleware `can:category.update`.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof Category ? $category->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('categories', 'name')->ignore($categoryId)],
            'requires_serial' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
