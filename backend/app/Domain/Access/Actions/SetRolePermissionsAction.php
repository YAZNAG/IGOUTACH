<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\CriticalPermissions;
use App\Domain\Access\Events\RolePermissionsChanged;
use App\Domain\Access\Exceptions\RoleManagementException;
use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;

/**
 * Remplace l'intégralité du jeu de permissions d'un rôle.
 * Garantit qu'au moins un rôle conserve chaque permission critique.
 */
final class SetRolePermissionsAction
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function execute(Role $role, array $permissionIds): Role
    {
        $this->assertCriticalPermissionsPreserved($role, $permissionIds);

        $role->permissions()->sync($permissionIds);

        RolePermissionsChanged::dispatch($role);

        return $role->load('permissions');
    }

    /**
     * @param  list<int>  $permissionIds
     */
    private function assertCriticalPermissionsPreserved(Role $role, array $permissionIds): void
    {
        $criticalIds = Permission::query()
            ->whereIn('name', CriticalPermissions::NAMES)
            ->pluck('id', 'name');

        foreach ($criticalIds as $name => $id) {
            if (in_array($id, $permissionIds, true)) {
                continue; // ce rôle la conserve
            }

            // Un autre rôle la porte-t-il encore ?
            $heldElsewhere = Permission::query()
                ->where('id', $id)
                ->whereHas('roles', fn ($q) => $q->whereKeyNot($role->getKey()))
                ->exists();

            if (! $heldElsewhere) {
                throw RoleManagementException::wouldLockOutAdmins((string) $name);
            }
        }
    }
}
