<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Warehouses\Models\WarehouseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('DEP-##')),
            'name' => $this->faker->company(),
            'warehouse_type_id' => WarehouseType::factory(),
            'city' => $this->faker->city(),
            'is_active' => true,
        ];
    }
}
