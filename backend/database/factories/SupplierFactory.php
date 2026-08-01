<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'code' => 'F'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'city' => $this->faker->city(),
            'payment_terms_days' => $this->faker->randomElement([0, 30, 60, 90]),
            'is_active' => true,
        ];
    }
}
