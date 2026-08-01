<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'code' => 'C'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->name(),
            'is_company' => false,
            'phone' => $this->faker->phoneNumber(),
            'city' => $this->faker->city(),
            'credit_limit' => 0,
            'balance' => 0,
            'is_blocked' => false,
            'is_active' => true,
        ];
    }
}
