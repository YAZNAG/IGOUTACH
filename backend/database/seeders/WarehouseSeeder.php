<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Warehouses\Models\WarehouseType;
use Illuminate\Database\Seeder;

final class WarehouseSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, type: string, city: string, parent?: string}>
     */
    public const WAREHOUSES = [
        ['code' => 'DEP-01', 'name' => 'Dépôt principal', 'type' => 'depot', 'city' => 'Casablanca'],
        ['code' => 'DEP-02', 'name' => 'Dépôt secondaire', 'type' => 'depot', 'city' => 'Rabat'],
        ['code' => 'POS-01', 'name' => 'Point de vente Agadir', 'type' => 'pos', 'city' => 'Agadir'],
        ['code' => 'VEH-01', 'name' => 'Véhicule — Vendeur A', 'type' => 'vehicle', 'city' => 'Casablanca', 'parent' => 'DEP-01'],
        ['code' => 'VEH-02', 'name' => 'Véhicule — Vendeur B', 'type' => 'vehicle', 'city' => 'Casablanca', 'parent' => 'DEP-01'],
    ];

    public function run(): void
    {
        $types = WarehouseType::pluck('id', 'code');

        foreach (self::WAREHOUSES as $warehouse) {
            $parentId = isset($warehouse['parent'])
                ? Warehouse::where('code', $warehouse['parent'])->value('id')
                : null;

            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                [
                    'name' => $warehouse['name'],
                    'warehouse_type_id' => $types[$warehouse['type']],
                    'parent_id' => $parentId,
                    'city' => $warehouse['city'],
                    'is_active' => true,
                ],
            );
        }
    }
}
