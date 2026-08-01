<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Repositories;

use App\Domain\Warehouses\Contracts\WarehouseRepositoryInterface;
use App\Domain\Warehouses\DTOs\WarehouseData;
use App\Domain\Warehouses\Models\Warehouse;

final class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function create(WarehouseData $data): Warehouse
    {
        return Warehouse::create($data->toAttributes());
    }

    public function update(Warehouse $warehouse, WarehouseData $data): Warehouse
    {
        $warehouse->update($data->toAttributes());

        return $warehouse->refresh();
    }
}
