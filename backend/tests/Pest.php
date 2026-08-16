<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Settings\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/**
 * Mode de règlement en espèces.
 *
 * Tout encaissement en porte un désormais : sans mode, il ne se rapproche
 * d'aucune caisse. Les tests doivent donc en fournir un, comme l'interface.
 */
function modeEspeces(): PaymentMethod
{
    return PaymentMethod::firstOrCreate(
        ['code' => 'CASH'],
        ['name' => 'Espèces', 'type' => 'cash', 'position' => 1, 'is_active' => true],
    );
}

/** Mode chèque — même type que la traite, même cycle. */
function modeCheque(): PaymentMethod
{
    return PaymentMethod::firstOrCreate(
        ['code' => 'CHEQUE'],
        ['name' => 'Chèque', 'type' => 'cheque', 'position' => 2, 'is_active' => true],
    );
}

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
