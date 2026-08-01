<?php

declare(strict_types=1);

namespace App\Domain\Access\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User;

    /**
     * Nombre d'administrateurs actifs autres que l'utilisateur donné.
     */
    public function otherActiveAdminsCount(User $except): int;
}
