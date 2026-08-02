<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'movement_type_id' => fn () => MovementType::firstOrCreate(
                ['code' => 'in'],
                ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true],
            )->id,
            'quantity' => $this->faker->numberBetween(1, 50),
            'unit_cost' => $this->faker->randomFloat(2, 5, 500),
            'balance_after' => $this->faker->numberBetween(0, 200),
        ];
    }
}
