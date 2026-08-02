<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
final class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(0, 100),
            'reserved_quantity' => 0,
            'average_cost' => $this->faker->randomFloat(2, 5, 500),
        ];
    }
}
