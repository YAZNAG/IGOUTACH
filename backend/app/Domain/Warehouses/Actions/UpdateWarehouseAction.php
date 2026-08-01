<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Actions;

use App\Domain\Warehouses\Contracts\WarehouseRepositoryInterface;
use App\Domain\Warehouses\DTOs\WarehouseData;
use App\Domain\Warehouses\Models\Warehouse;

final class UpdateWarehouseAction
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
    ) {}

    public function execute(Warehouse $warehouse, WarehouseData $data): Warehouse
    {
        return $this->warehouses->update($warehouse, $data);
    }
}
