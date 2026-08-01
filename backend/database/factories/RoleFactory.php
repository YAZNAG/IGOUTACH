<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Access\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => $name,
            'display_name' => ucfirst($name),
            'description' => $this->faker->sentence(),
            'is_system' => false,
        ];
    }
}
