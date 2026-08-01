<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Contracts;

use App\Domain\Warehouses\DTOs\WarehouseData;
use App\Domain\Warehouses\Models\Warehouse;

interface WarehouseRepositoryInterface
{
    public function create(WarehouseData $data): Warehouse;

    public function update(Warehouse $warehouse, WarehouseData $data): Warehouse;
}
