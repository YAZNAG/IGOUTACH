<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('U??'),
            'name' => $this->faker->word(),
        ];
    }
}
