<?php

declare(strict_types=1);

namespace App\Domain\Access\Repositories;

use App\Domain\Access\Contracts\UserRepositoryInterface;
use App\Models\User;

final class UserRepository implements UserRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    public function otherActiveAdminsCount(User $except): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($except->getKey())
            ->whereHas('roles.permissions', fn ($q) => $q->where('name', 'role.manage'))
            ->count();
    }
}
