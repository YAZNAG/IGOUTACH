<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Contracts\UserRepositoryInterface;
use App\Domain\Access\DTOs\UserData;
use App\Domain\Access\Events\UserCreated;
use App\Domain\Access\Exceptions\UserManagementException;
use App\Domain\Access\Models\Role;
use App\Domain\Access\Notifications\UserInvitationNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crée un utilisateur par invitation : aucun mot de passe saisi par l'admin,
 * l'utilisateur reçoit un lien signé (72 h) pour définir le sien.
 */
final class CreateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AssignRolesAction $assignRoles,
    ) {}

    public function execute(UserData $data, ?User $author = null): User
    {
        $this->assertWarehouseConsistency($data);

        return DB::transaction(function () use ($data, $author): User {
            $user = $this->users->create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'warehouse_id' => $data->warehouseId,
                'is_active' => $data->isActive,
                // Mot de passe temporaire inutilisable : remplacé par l'invitation.
                'password' => Str::password(32),
            ]);

            $this->assignRoles->execute($user, $data->roleIds, $author);

            UserCreated::dispatch($user);

            $token = UserInvitationNotification::tokenFor($user);
            $user->notify(new UserInvitationNotification($token));

            return $user->load('roles');
        });
    }

    /**
     * Un utilisateur sans rôle « accès global » (stock.view_global) doit avoir un lieu.
     */
    private function assertWarehouseConsistency(UserData $data): void
    {
        if ($data->warehouseId === null) {
            $hasGlobalAccess = Role::query()
                ->whereIn('id', $data->roleIds)
                ->whereHas('permissions', fn ($q) => $q->where('name', 'stock.view_global'))
                ->exists();

            if (! $hasGlobalAccess) {
                throw UserManagementException::warehouseRequired();
            }

            return;
        }

        WarehouseAssignmentGuard::assertVehicleFree($data->warehouseId, null);
    }
}
