<?php

declare(strict_types=1);

namespace App\Domain\Access\Listeners;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Domain\Access\Events\RoleAssigned;
use App\Domain\Access\Events\RolePermissionsChanged;
use App\Domain\Access\Events\RoleRevoked;
use App\Domain\Access\Events\UserCreated;
use App\Domain\Access\Events\UserDeactivated;
use App\Domain\Access\Events\UserPermissionChanged;
use App\Models\User;
use Illuminate\Events\Dispatcher;

/**
 * Trace automatiquement les événements sensibles d'accès dans le journal d'audit.
 * Abonné auto-enregistré via Event::subscribe(...) dans AccessServiceProvider.
 */
final class AuditEventSubscriber
{
    public function __construct(private readonly AuditLoggerInterface $audit) {}

    public function onUserCreated(UserCreated $event): void
    {
        $this->userAction('user.created', 'Utilisateur créé', $event->user);
    }

    public function onUserDeactivated(UserDeactivated $event): void
    {
        $this->userAction('user.deactivated', 'Utilisateur désactivé', $event->user);
    }

    public function onUserPermissionChanged(UserPermissionChanged $event): void
    {
        $this->userAction('user.permission_changed', 'Dérogation de permission modifiée', $event->user);
    }

    public function onRoleAssigned(RoleAssigned $event): void
    {
        $this->userAction('role.assigned', 'Rôles assignés à un utilisateur', $event->user);
    }

    public function onRoleRevoked(RoleRevoked $event): void
    {
        $this->userAction('role.revoked', 'Rôle retiré à un utilisateur', $event->user);
    }

    public function onRolePermissionsChanged(RolePermissionsChanged $event): void
    {
        $this->audit->log(
            action: 'role.permissions_changed',
            description: 'Permissions du rôle « '.$event->role->name.' » modifiées',
            entityType: $event->role::class,
            entityId: $event->role->id,
            module: 'access',
        );
    }

    private function userAction(string $action, string $description, User $user): void
    {
        $this->audit->log(
            action: $action,
            description: $description.' : '.$user->email,
            entityType: $user::class,
            entityId: $user->id,
            module: 'access',
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            UserCreated::class => 'onUserCreated',
            UserDeactivated::class => 'onUserDeactivated',
            UserPermissionChanged::class => 'onUserPermissionChanged',
            RoleAssigned::class => 'onRoleAssigned',
            RoleRevoked::class => 'onRoleRevoked',
            RolePermissionsChanged::class => 'onRolePermissionsChanged',
        ];
    }
}
