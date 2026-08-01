<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Domain\Access\Contracts\UserRepositoryInterface;
use App\Domain\Access\Listeners\AuditEventSubscriber;
use App\Domain\Access\Listeners\PermissionCacheInvalidator;
use App\Domain\Access\Repositories\UserRepository;
use App\Domain\Access\Services\AuditLogger;
use App\Domain\Access\Services\PermissionResolver;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AccessServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PermissionResolverInterface::class => PermissionResolver::class,
        UserRepositoryInterface::class => UserRepository::class,
        AuditLoggerInterface::class => AuditLogger::class,
    ];

    public function boot(): void
    {
        // Toute vérification d'autorisation ($user->can('module.action'))
        // passe par les permissions effectives, jamais par un nom de rôle.
        Gate::before(function (User $user, string $ability): ?bool {
            $resolver = app(PermissionResolverInterface::class);

            return $resolver->has($user, $ability) ? true : null;
        });

        // Invalidation du cache des permissions sur tout changement d'accès.
        Event::subscribe(PermissionCacheInvalidator::class);

        // Journalisation d'audit des événements sensibles d'accès.
        Event::subscribe(AuditEventSubscriber::class);
    }
}
