<?php

declare(strict_types=1);

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Models\User;

/**
 * Crée un utilisateur doté d'un rôle possédant les permissions données.
 *
 * @param  list<string>  $permissionNames
 */
function userWithPermissions(array $permissionNames): User
{
    $role = Role::factory()->create();

    foreach ($permissionNames as $name) {
        $permission = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $name, 'module' => explode('.', $name)[0]],
        );
        $role->permissions()->attach($permission);
    }

    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

it('accorde les permissions héritées des rôles', function () {
    $user = userWithPermissions(['stock.view', 'sale.create']);
    $resolver = app(PermissionResolverInterface::class);

    expect($resolver->effectivePermissions($user))
        ->toContain('stock.view')
        ->toContain('sale.create');
    expect($resolver->has($user, 'stock.view'))->toBeTrue();
    expect($resolver->has($user, 'stock.adjust'))->toBeFalse();
});

it('ajoute une permission via une dérogation accordée', function () {
    $user = userWithPermissions(['stock.view']);
    $extra = Permission::firstOrCreate(
        ['name' => 'stock.adjust'],
        ['display_name' => 'Ajuster', 'module' => 'stock'],
    );
    $user->permissionOverrides()->attach($extra, ['is_granted' => true]);

    $resolver = app(PermissionResolverInterface::class);
    $resolver->forget($user);

    expect($resolver->has($user, 'stock.adjust'))->toBeTrue();
});

it('le refus explicite l\'emporte sur la permission d\'un rôle', function () {
    $user = userWithPermissions(['stock.view', 'sale.create']);
    $denied = Permission::where('name', 'sale.create')->firstOrFail();
    $user->permissionOverrides()->attach($denied, ['is_granted' => false]);

    $resolver = app(PermissionResolverInterface::class);
    $resolver->forget($user);

    expect($resolver->has($user, 'sale.create'))->toBeFalse();
    expect($resolver->has($user, 'stock.view'))->toBeTrue();
});

it('expose les permissions via la Gate Laravel ($user->can())', function () {
    $user = userWithPermissions(['stock.view']);

    expect($user->can('stock.view'))->toBeTrue();
    expect($user->can('stock.view_global'))->toBeFalse();
});

it('met en cache et invalide correctement les permissions', function () {
    $user = userWithPermissions(['stock.view']);
    $resolver = app(PermissionResolverInterface::class);

    // Amorce le cache.
    expect($resolver->has($user, 'stock.view'))->toBeTrue();

    // Ajoute une permission en base sans invalider : le cache masque le changement.
    $extra = Permission::firstOrCreate(
        ['name' => 'stock.adjust'],
        ['display_name' => 'Ajuster', 'module' => 'stock'],
    );
    $user->roles()->first()->permissions()->attach($extra);

    expect($resolver->has($user, 'stock.adjust'))->toBeFalse();

    // Après invalidation, le changement est visible.
    $resolver->forget($user);
    expect($resolver->has($user, 'stock.adjust'))->toBeTrue();
});
