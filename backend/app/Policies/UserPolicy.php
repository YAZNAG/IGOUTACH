<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Règles d'autorisation propres aux utilisateurs. Les protections « soi-même »
 * tiennent même pour un administrateur (elles ne passent pas par une permission).
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('user.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('user.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('user.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('user.update');
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->can('user.deactivate') && ! $actor->is($target);
    }

    public function assignRoles(User $actor, User $target): bool
    {
        return $actor->can('user.assign_role') && ! $actor->is($target);
    }
}
