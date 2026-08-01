<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Actions\DeleteRoleAction;
use App\Domain\Access\Actions\DuplicateRoleAction;
use App\Domain\Access\Actions\SetRolePermissionsAction;
use App\Domain\Access\Exceptions\RoleManagementException;
use App\Domain\Access\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateRoleRequest;
use App\Http\Requests\SetRolePermissionsRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class RoleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->with('permissions:id')
            ->orderByDesc('level')
            ->orderBy('display_name')
            ->get();

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        /** @var array{name: string, display_name: string, description?: string|null, level?: int} $data */
        $data = $request->validated();

        $role = Role::query()->create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'level' => $data['level'] ?? 0,
        ]);

        return RoleResource::make($role->loadCount(['users', 'permissions']))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        /** @var array{name: string, display_name: string, description?: string|null, level?: int} $data */
        $data = $request->validated();

        $role->update([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'level' => $data['level'] ?? $role->level,
        ]);

        return RoleResource::make($role->loadCount(['users', 'permissions']));
    }

    public function destroy(Role $role, DeleteRoleAction $action): JsonResponse|Response
    {
        try {
            $action->execute($role);
        } catch (RoleManagementException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }

    public function permissions(SetRolePermissionsRequest $request, Role $role, SetRolePermissionsAction $action): JsonResponse|RoleResource
    {
        /** @var array{permission_ids: list<int>} $data */
        $data = $request->validated();

        try {
            $action->execute($role, $data['permission_ids']);
        } catch (RoleManagementException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return RoleResource::make($role->loadCount(['users', 'permissions'])->load('permissions:id'));
    }

    public function duplicate(DuplicateRoleRequest $request, Role $role, DuplicateRoleAction $action): JsonResponse
    {
        /** @var array{name: string, display_name: string} $data */
        $data = $request->validated();

        $copy = $action->execute($role, $data['name'], $data['display_name']);

        return RoleResource::make($copy->loadCount(['users', 'permissions']))
            ->response()
            ->setStatusCode(201);
    }
}
