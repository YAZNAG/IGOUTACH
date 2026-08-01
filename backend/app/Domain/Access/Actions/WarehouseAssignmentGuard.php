<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Exceptions\UserManagementException;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;

/**
 * Règle métier : un véhicule ne peut avoir qu'un seul vendeur rattaché.
 */
final class WarehouseAssignmentGuard
{
    public static function assertVehicleFree(int $warehouseId, ?int $exceptUserId): void
    {
        $warehouse = Warehouse::query()->find($warehouseId);

        if ($warehouse === null || ! $warehouse->isVehicle()) {
            return;
        }

        $occupied = User::query()
            ->where('warehouse_id', $warehouseId)
            ->when($exceptUserId !== null, fn ($q) => $q->whereKeyNot($exceptUserId))
            ->exists();

        if ($occupied) {
            throw UserManagementException::vehicleHasSeller();
        }
    }
}
