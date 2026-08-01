<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordre d'exécution imposé par le brief (§14).
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            AdminSeeder::class,
            WarehouseTypeSeeder::class,
            WarehouseSeeder::class,
            UnitSeeder::class,
            TaxRateSeeder::class,
            BrandSeeder::class,
            CatalogSeeder::class,
            PriceTypeSeeder::class,
            CatalogImportSeeder::class,
            MovementTypeSeeder::class,
            TransferStatusSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
