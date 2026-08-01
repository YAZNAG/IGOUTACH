<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Access\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $module = $this->faker->randomElement(['stock', 'sales', 'catalog', 'access']);
        $action = $this->faker->unique()->word();

        return [
            'name' => "{$module}.{$action}",
            'display_name' => ucfirst($action),
            'module' => $module,
            'description' => $this->faker->sentence(),
        ];
    }
}
