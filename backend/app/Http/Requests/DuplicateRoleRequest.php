<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DuplicateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('role.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', 'unique:roles,name'],
            'display_name' => ['required', 'string', 'max:120'],
        ];
    }
}
