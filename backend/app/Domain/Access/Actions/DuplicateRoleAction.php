<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Duplique un rôle avec toutes ses permissions (jamais système, non attribué).
 */
final class DuplicateRoleAction
{
    public function execute(Role $role, string $name, string $displayName): Role
    {
        return DB::transaction(function () use ($role, $name, $displayName): Role {
            $copy = Role::query()->create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $role->description,
                'is_system' => false,
                'level' => $role->level,
            ]);

            $copy->permissions()->sync($role->permissions()->pluck('permissions.id')->all());

            return $copy->load('permissions');
        });
    }
}
