<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Actions\AssignRolesAction;
use App\Domain\Access\Actions\CreateUserAction;
use App\Domain\Access\Actions\ResetUserPasswordAction;
use App\Domain\Access\Actions\ToggleUserAction;
use App\Domain\Access\Actions\UpdateUserAction;
use App\Domain\Access\DTOs\UserData;
use App\Domain\Access\Exceptions\UserManagementException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRolesRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Support\Query\Sortable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    private function perPage(Request $request): int
    {
        $requested = $request->integer('per_page', 20);

        return in_array($requested, [20, 50, 100], true) ? $requested : 20;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->with(['roles:id,name,display_name,level', 'warehouse:id,code,name'])
            ->when($request->string('q')->isNotEmpty(), function ($q) use ($request) {
                $term = $request->string('q')->value();
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->integer('role_id') > 0, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('roles.id', $request->integer('role_id'))));

        Sortable::apply($query, $request, [
            'name' => 'name',
            'email' => 'email',
            'last_login_at' => 'last_login_at',
            'created_at' => 'created_at',
        ], 'name');

        return AdminUserResource::collection($query->paginate($this->perPage($request)));
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        try {
            $user = $action->execute(UserData::fromArray($request->validated()), $request->user());
        } catch (UserManagementException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return AdminUserResource::make($user->load(['roles', 'warehouse']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): AdminUserResource
    {
        return AdminUserResource::make($user->load(['roles', 'warehouse']));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse|AdminUserResource
    {
        try {
            $updated = $action->execute($user, UserData::fromArray($request->validated() + ['role_ids' => []]));
        } catch (UserManagementException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return AdminUserResource::make($updated->load(['roles', 'warehouse']));
    }

    public function toggle(Request $request, User $user, ToggleUserAction $action): JsonResponse|AdminUserResource
    {
        /** @var User $author */
        $author = $request->user();

        try {
            $action->execute($user, $author);
        } catch (UserManagementException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return AdminUserResource::make($user->load(['roles', 'warehouse']));
    }

    public function roles(AssignRolesRequest $request, User $user, AssignRolesAction $action): JsonResponse|AdminUserResource
    {
        Gate::authorize('assignRoles', $user);

        /** @var array{role_ids: list<int>} $data */
        $data = $request->validated();

        try {
            $action->execute($user, $data['role_ids'], $request->user());
        } catch (UserManagementException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return AdminUserResource::make($user->load(['roles', 'warehouse']));
    }

    public function resendInvitation(User $user, ResetUserPasswordAction $action): JsonResponse
    {
        $action->execute($user);

        return response()->json(['message' => 'Invitation renvoyée.']);
    }

    public function forcePasswordReset(User $user, ResetUserPasswordAction $action): JsonResponse
    {
        $action->execute($user);

        return response()->json(['message' => 'Lien de réinitialisation envoyé.']);
    }
}
