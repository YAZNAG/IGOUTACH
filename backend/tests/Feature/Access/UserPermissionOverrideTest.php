<?php

declare(strict_types=1);

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Domain\Access\Events\UserPermissionChanged;
use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Models\User;

function permission(string $name): Permission
{
    return Permission::firstOrCreate(
        ['name' => $name],
        ['display_name' => $name, 'module' => explode('.', $name)[0]],
    );
}

it('le refus explicite gagne même sur un rôle qui accorde tout', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach([permission('stock.view')->id, permission('stock.adjust')->id]);

    $user = User::factory()->create();
    $user->roles()->attach($role);
    $user->permissionOverrides()->attach(permission('stock.adjust')->id, ['is_granted' => false]);

    $resolver = app(PermissionResolverInterface::class);

    expect($resolver->has($user, 'stock.view'))->toBeTrue();
    expect($resolver->has($user, 'stock.adjust'))->toBeFalse();
});

it('ignore une dérogation accordée expirée', function () {
    $user = User::factory()->create();
    $user->permissionOverrides()->attach(permission('stock.adjust')->id, [
        'is_granted' => true,
        'expires_at' => now()->subDay(),
    ]);

    expect(app(PermissionResolverInterface::class)->has($user, 'stock.adjust'))->toBeFalse();
});

it('une dérogation accordée non expirée reste active', function () {
    $user = User::factory()->create();
    $user->permissionOverrides()->attach(permission('stock.adjust')->id, [
        'is_granted' => true,
        'expires_at' => now()->addDay(),
    ]);

    expect(app(PermissionResolverInterface::class)->has($user, 'stock.adjust'))->toBeTrue();
});

it('un retrait expiré n\'a plus d\'effet et restaure la permission du rôle', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach(permission('stock.view')->id);

    $user = User::factory()->create();
    $user->roles()->attach($role);
    $user->permissionOverrides()->attach(permission('stock.view')->id, [
        'is_granted' => false,
        'expires_at' => now()->subHour(),
    ]);

    expect(app(PermissionResolverInterface::class)->has($user, 'stock.view'))->toBeTrue();
});

it('invalide le cache lorsqu\'une dérogation change (événement)', function () {
    $user = User::factory()->create();
    $resolver = app(PermissionResolverInterface::class);

    // Amorce le cache : aucune permission.
    expect($resolver->has($user, 'stock.adjust'))->toBeFalse();

    $user->permissionOverrides()->attach(permission('stock.adjust')->id, ['is_granted' => true]);
    UserPermissionChanged::dispatch($user);

    expect($resolver->has($user, 'stock.adjust'))->toBeTrue();
});

it('la commande de purge supprime les dérogations expirées', function () {
    $user = User::factory()->create();
    $user->permissionOverrides()->attach(permission('stock.adjust')->id, [
        'is_granted' => true,
        'expires_at' => now()->subDay(),
    ]);
    $user->permissionOverrides()->attach(permission('stock.view')->id, [
        'is_granted' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('permissions:purge-expired')->assertSuccessful();

    $this->assertDatabaseMissing('user_permission', [
        'user_id' => $user->id,
        'permission_id' => permission('stock.adjust')->id,
    ]);
    $this->assertDatabaseHas('user_permission', [
        'user_id' => $user->id,
        'permission_id' => permission('stock.view')->id,
    ]);
});

it('expose les dérogations et la source via l\'API', function () {
    $admin = grantUser(['user.view', 'user.manage_permissions']);
    $target = User::factory()->create();
    permission('stock.adjust');

    // POST : accorde une dérogation
    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/permissions", [
            'permission' => 'stock.adjust',
            'is_granted' => true,
            'reason' => 'Remplacement congés',
        ])
        ->assertOk()
        ->assertJsonPath('data.overrides.0.permission', 'stock.adjust');

    // GET : la permission effective a pour source « granted »
    $this->actingAs($admin)
        ->getJson("/api/v1/users/{$target->id}/permissions")
        ->assertOk()
        ->assertJsonPath('data.effective.0.source', 'granted');

    // DELETE : retire la dérogation
    $permId = Permission::where('name', 'stock.adjust')->value('id');
    $this->actingAs($admin)
        ->deleteJson("/api/v1/users/{$target->id}/permissions/{$permId}")
        ->assertOk();

    $this->assertDatabaseMissing('user_permission', [
        'user_id' => $target->id,
        'permission_id' => $permId,
    ]);
});

it('refuse la gestion des dérogations sans la permission', function () {
    $user = grantUser(['user.view']);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/users/{$target->id}/permissions", [
            'permission' => 'stock.adjust',
            'is_granted' => true,
        ])
        ->assertForbidden();
});
