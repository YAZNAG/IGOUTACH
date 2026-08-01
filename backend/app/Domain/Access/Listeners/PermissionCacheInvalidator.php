<?php

declare(strict_types=1);

namespace App\Domain\Access\Listeners;

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Domain\Access\Events\RoleAssigned;
use App\Domain\Access\Events\RolePermissionsChanged;
use App\Domain\Access\Events\RoleRevoked;
use App\Domain\Access\Events\UserDeactivated;
use App\Domain\Access\Events\UserPermissionChanged;
use App\Models\User;
use Illuminate\Events\Dispatcher;

/**
 * Invalide le cache des permissions dès qu'un rôle, une permission de rôle
 * ou une dérogation individuelle change.
 */
final class PermissionCacheInvalidator
{
    public function __construct(
        private readonly PermissionResolverInterface $resolver,
    ) {}

    public function onUserChanged(RoleAssigned|RoleRevoked|UserPermissionChanged|UserDeactivated $event): void
    {
        $this->resolver->forget($event->user);
    }

    /**
     * Un changement de permissions de rôle touche tous ses utilisateurs.
     */
    public function onRolePermissionsChanged(RolePermissionsChanged $event): void
    {
        $event->role->users()->each(function (User $user): void {
            $this->resolver->forget($user);
        });
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            RoleAssigned::class => 'onUserChanged',
            RoleRevoked::class => 'onUserChanged',
            UserPermissionChanged::class => 'onUserChanged',
            UserDeactivated::class => 'onUserChanged',
            RolePermissionsChanged::class => 'onRolePermissionsChanged',
        ];
    }
}
