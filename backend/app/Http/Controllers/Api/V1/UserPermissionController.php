<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Domain\Access\Events\UserPermissionChanged;
use App\Domain\Access\Models\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserPermissionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class UserPermissionController extends Controller
{
    public function __construct(
        private readonly PermissionResolverInterface $resolver,
    ) {}

    /**
     * Permissions effectives (avec leur origine) + dérogations brutes.
     */
    public function index(User $user): JsonResponse
    {
        $user->load(['roles.permissions:id,name,display_name,module', 'permissionOverrides']);

        // permission.name => nom du premier rôle qui la porte
        $roleSource = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (! array_key_exists($permission->name, $roleSource)) {
                    $roleSource[$permission->name] = $role->name;
                }
            }
        }

        $now = now();
        $activeGranted = [];
        foreach ($user->permissionOverrides as $permission) {
            $expiresAt = $permission->pivot->expires_at;
            $active = $expiresAt === null || $now->lessThan(Carbon::parse((string) $expiresAt));
            if ($active && (bool) $permission->pivot->is_granted === true) {
                $activeGranted[$permission->name] = true;
            }
        }

        /** @var array<string, array{display_name: string, module: string}> $catalogue */
        $catalogue = Permission::query()
            ->get(['name', 'display_name', 'module'])
            ->mapWithKeys(fn (Permission $p): array => [
                $p->name => ['display_name' => $p->display_name, 'module' => $p->module],
            ])
            ->all();

        $effective = [];
        foreach ($this->resolver->effectivePermissions($user) as $name) {
            $meta = $catalogue[$name] ?? ['display_name' => $name, 'module' => 'divers'];
            $effective[] = [
                'name' => $name,
                'display_name' => $meta['display_name'],
                'module' => $meta['module'],
                'source' => array_key_exists($name, $activeGranted)
                    ? 'granted'
                    : (array_key_exists($name, $roleSource) ? 'role:'.$roleSource[$name] : 'granted'),
            ];
        }

        $overrides = $user->permissionOverrides->map(function (Permission $permission) use ($now): array {
            $expiresAt = $permission->pivot->expires_at;

            return [
                'permission_id' => $permission->id,
                'permission' => $permission->name,
                'display_name' => $permission->display_name,
                'module' => $permission->module,
                'is_granted' => (bool) $permission->pivot->is_granted,
                'reason' => $permission->pivot->reason,
                'expires_at' => $expiresAt,
                'granted_by' => $permission->pivot->granted_by,
                'expired' => $expiresAt !== null && $now->greaterThanOrEqualTo(Carbon::parse((string) $expiresAt)),
            ];
        })->all();

        return response()->json([
            'data' => [
                'effective' => $effective,
                'overrides' => $overrides,
            ],
        ]);
    }

    public function store(StoreUserPermissionRequest $request, User $user): JsonResponse
    {
        /** @var array{permission: string, is_granted: bool, reason?: string|null, expires_at?: string|null} $data */
        $data = $request->validated();

        $permissionId = (int) Permission::query()->where('name', $data['permission'])->value('id');

        $user->permissionOverrides()->syncWithoutDetaching([
            $permissionId => [
                'is_granted' => $data['is_granted'],
                'granted_by' => $request->user()?->id,
                'reason' => $data['reason'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ],
        ]);

        UserPermissionChanged::dispatch($user);

        return $this->index($user);
    }

    public function destroy(User $user, Permission $permission): JsonResponse
    {
        $user->permissionOverrides()->detach($permission->id);

        UserPermissionChanged::dispatch($user);

        return $this->index($user);
    }
}
