<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Domain\AccessServiceProvider;
use App\Providers\Domain\CatalogServiceProvider;
use App\Providers\Domain\PricingServiceProvider;
use App\Providers\Domain\SettingsServiceProvider;
use App\Providers\Domain\StockServiceProvider;
use App\Providers\Domain\WarehousesServiceProvider;

return [
    AppServiceProvider::class,
    AccessServiceProvider::class,
    CatalogServiceProvider::class,
    PricingServiceProvider::class,
    SettingsServiceProvider::class,
    StockServiceProvider::class,
    WarehousesServiceProvider::class,
];
