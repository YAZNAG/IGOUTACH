<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Exceptions\RoleManagementException;
use App\Domain\Access\Models\Role;

/**
 * Supprime un rôle : interdit s'il est système ou encore attribué.
 */
final class DeleteRoleAction
{
    public function execute(Role $role): void
    {
        if ($role->is_system) {
            throw RoleManagementException::systemRole();
        }

        $count = $role->users()->count();
        if ($count > 0) {
            throw RoleManagementException::roleInUse($count);
        }

        $role->permissions()->detach();
        $role->delete();
    }
}
