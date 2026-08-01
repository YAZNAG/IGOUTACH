<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Access\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
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
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : null;

        return [
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'name')->ignore($roleId)],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['integer', 'min:0', 'max:100'],
        ];
    }
}
