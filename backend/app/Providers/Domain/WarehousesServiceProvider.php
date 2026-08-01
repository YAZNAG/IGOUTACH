<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Warehouses\Contracts\WarehouseRepositoryInterface;
use App\Domain\Warehouses\Repositories\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

final class WarehousesServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        WarehouseRepositoryInterface::class => WarehouseRepository::class,
    ];
}
