<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de test : attribue un stock à chaque article dans chaque lieu.
 * Quantité indicative selon le type de lieu ; coût = prix d'achat de l'article.
 */
final class DemoStockSeeder extends Seeder
{
    /**
     * @var array<string, int>
     */
    private const BASE_BY_TYPE = [
        'depot' => 30,
        'pos' => 10,
        'vehicle' => 4,
    ];

    public function run(): void
    {
        // Base de quantité par lieu, calculée une fois via le code de type.
        /** @var array<int, int> $baseByWarehouse */
        $baseByWarehouse = [];

        DB::table('warehouses')
            ->join('warehouse_types', 'warehouse_types.id', '=', 'warehouses.warehouse_type_id')
            ->where('warehouses.is_active', true)
            ->get(['warehouses.id', 'warehouse_types.code'])
            ->each(function (\stdClass $row) use (&$baseByWarehouse): void {
                $baseByWarehouse[(int) $row->id] = self::BASE_BY_TYPE[(string) $row->code] ?? 10;
            });

        Product::query()->orderBy('id')->chunkById(100, function ($products) use ($baseByWarehouse): void {
            foreach ($products as $product) {
                $cost = (float) $product->cost_price;

                foreach ($baseByWarehouse as $warehouseId => $base) {
                    Stock::withoutGlobalScopes()->updateOrCreate(
                        ['warehouse_id' => $warehouseId, 'product_id' => $product->id],
                        ['quantity' => $base + ($product->id % 12), 'average_cost' => (string) ($cost > 0 ? $cost : 0)],
                    );
                }
            }
        });
    }
}
