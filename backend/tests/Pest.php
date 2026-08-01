<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/**
 * Crée un utilisateur doté d'un rôle possédant les permissions données.
 *
 * @param  list<string>  $permissionNames
 * @param  array<string, mixed>  $attributes
 */
function grantUser(array $permissionNames = [], array $attributes = []): User
{
    $role = Role::factory()->create();

    foreach ($permissionNames as $name) {
        $permission = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $name, 'module' => explode('.', $name)[0]],
        );
        $role->permissions()->attach($permission);
    }

    $user = User::factory()->create($attributes);
    $user->roles()->attach($role);

    return $user;
}
