<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Actions;

use App\Domain\Warehouses\Contracts\WarehouseRepositoryInterface;
use App\Domain\Warehouses\DTOs\WarehouseData;
use App\Domain\Warehouses\Models\Warehouse;

final class CreateWarehouseAction
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
    ) {}

    public function execute(WarehouseData $data): Warehouse
    {
        return $this->warehouses->create($data);
    }
}
