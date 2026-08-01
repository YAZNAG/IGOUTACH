<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Warehouses\Models\WarehouseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseType>
 */
class WarehouseTypeFactory extends Factory
{
    protected $model = WarehouseType::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(1),
            'name' => $this->faker->word(),
            'allows_sales' => true,
            'allows_purchase_receipt' => true,
            'requires_transfer_approval' => true,
        ];
    }
}
