<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Contracts\UserRepositoryInterface;
use App\Domain\Access\DTOs\UserData;
use App\Domain\Access\Exceptions\UserManagementException;
use App\Models\User;

/**
 * Met à jour les informations d'un utilisateur (hors rôles, gérés à part).
 */
final class UpdateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function execute(User $user, UserData $data): User
    {
        $this->assertWarehouseConsistency($user, $data);

        return $this->users->update($user, [
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'warehouse_id' => $data->warehouseId,
        ]);
    }

    /**
     * Un utilisateur sans accès global doit conserver un lieu de rattachement.
     */
    private function assertWarehouseConsistency(User $user, UserData $data): void
    {
        if ($data->warehouseId === null) {
            $hasGlobalAccess = $user->roles()
                ->whereHas('permissions', fn ($q) => $q->where('name', 'stock.view_global'))
                ->exists();

            if (! $hasGlobalAccess) {
                throw UserManagementException::warehouseRequired();
            }

            return;
        }

        WarehouseAssignmentGuard::assertVehicleFree($data->warehouseId, $user->id);
    }
}
