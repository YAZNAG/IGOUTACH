<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Warehouses\Models\WarehouseType;
use Illuminate\Database\Seeder;

final class WarehouseTypeSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, allows_sales: bool, allows_purchase_receipt: bool, requires_transfer_approval: bool}>
     */
    public const TYPES = [
        ['code' => 'depot', 'name' => 'Dépôt', 'allows_sales' => false, 'allows_purchase_receipt' => true, 'requires_transfer_approval' => true],
        ['code' => 'pos', 'name' => 'Point de vente', 'allows_sales' => true, 'allows_purchase_receipt' => true, 'requires_transfer_approval' => true],
        ['code' => 'vehicle', 'name' => 'Véhicule vendeur', 'allows_sales' => true, 'allows_purchase_receipt' => false, 'requires_transfer_approval' => true],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            WarehouseType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
