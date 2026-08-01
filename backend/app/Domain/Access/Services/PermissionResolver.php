<?php

declare(strict_types=1);

namespace App\Domain\Access\Services;

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;

/**
 * Résout et met en cache les permissions effectives d'un utilisateur.
 *
 * Résultat = (permissions des rôles ∪ permissions accordées) − permissions refusées.
 * Le refus explicite (user_permission.is_granted = false) l'emporte toujours.
 */
final class PermissionResolver implements PermissionResolverInterface
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @return list<string>
     */
    public function effectivePermissions(User $user): array
    {
        /** @var list<string> $permissions */
        $permissions = $this->cache->remember(
            $this->cacheKey($user),
            self::CACHE_TTL,
            fn (): array => $this->compute($user),
        );

        return $permissions;
    }

    public function has(User $user, string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions($user), true);
    }

    public function forget(User $user): void
    {
        $this->cache->forget($this->cacheKey($user));
    }

    /**
     * @return list<string>
     */
    private function compute(User $user): array
    {
        $fromRoles = $user->roles()
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->all();

        // Les dérogations expirées sont ignorées (elles se referment d'elles-mêmes).
        $now = now();
        $overrides = $user->permissionOverrides()
            ->get()
            ->filter(function ($permission) use ($now): bool {
                /** @var string|null $expiresAt */
                $expiresAt = $permission->pivot->expires_at;

                return $expiresAt === null || $now->lessThan(Carbon::parse($expiresAt));
            });

        $granted = $overrides
            ->where('pivot.is_granted', true)
            ->pluck('name')
            ->all();

        $denied = $overrides
            ->where('pivot.is_granted', false)
            ->pluck('name')
            ->all();

        $effective = array_diff(
            array_unique([...$fromRoles, ...$granted]),
            $denied,
        );

        return array_values($effective);
    }

    private function cacheKey(User $user): string
    {
        return "permissions:user:{$user->getKey()}";
    }
}
