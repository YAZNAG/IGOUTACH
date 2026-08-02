<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Contracts\UserRepositoryInterface;
use App\Domain\Access\DTOs\UserData;
use App\Domain\Access\Events\UserCreated;
use App\Domain\Access\Exceptions\UserManagementException;
use App\Domain\Access\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crée un utilisateur directement : l'admin saisit l'e-mail et le mot de passe.
 * Aucune invitation n'est envoyée.
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
                // Mot de passe saisi par l'admin (haché via le cast) ; fallback aléatoire si absent.
                'password' => $data->password ?? Str::password(32),
            ]);

            $this->assignRoles->execute($user, $data->roleIds, $author);

            UserCreated::dispatch($user);

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
