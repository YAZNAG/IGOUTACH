<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Events\RoleAssigned;
use App\Domain\Access\Events\RoleRevoked;
use App\Domain\Access\Exceptions\UserManagementException;
use App\Domain\Access\Models\Role;
use App\Models\User;

/**
 * Attribue un jeu de rôles à un utilisateur.
 * Un auteur ne peut attribuer que des rôles de rang ≤ au sien.
 */
final class AssignRolesAction
{
    /**
     * @param  list<int>  $roleIds
     */
    public function execute(User $target, array $roleIds, ?User $author = null): User
    {
        $roles = Role::query()->whereIn('id', $roleIds)->get();

        if ($author !== null) {
            $authorLevel = $this->maxLevel($author);
            foreach ($roles as $role) {
                if ($role->level > $authorLevel) {
                    throw UserManagementException::roleAboveOwnRank();
                }
            }
        }

        $pivot = [];
        foreach ($roles as $role) {
            $pivot[$role->id] = ['assigned_at' => now(), 'assigned_by' => $author?->id];
        }

        $changes = $target->roles()->sync($pivot);

        if ($changes['attached'] !== [] || $changes['detached'] !== []) {
            if ($changes['attached'] !== []) {
                RoleAssigned::dispatch($target);
            }
            if ($changes['detached'] !== []) {
                RoleRevoked::dispatch($target);
            }
        }

        return $target->load('roles');
    }

    private function maxLevel(User $user): int
    {
        return (int) $user->roles()->max('level');
    }
}
