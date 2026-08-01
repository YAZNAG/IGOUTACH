<?php

declare(strict_types=1);

namespace App\Domain\Access\Contracts;

use App\Models\User;

interface PermissionResolverInterface
{
    /**
     * Permissions effectives de l'utilisateur :
     * (permissions des rôles ∪ permissions accordées) − permissions refusées.
     *
     * @return list<string>
     */
    public function effectivePermissions(User $user): array;

    public function has(User $user, string $permission): bool;

    /**
     * Invalide le cache des permissions d'un utilisateur.
     */
    public function forget(User $user): void;
}
