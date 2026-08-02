<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Crée un acteur doté d'un rôle de rang donné possédant les permissions listées.
 *
 * @param  list<string>  $perms
 */
function actorWith(array $perms, int $level = 100): User
{
    $role = Role::factory()->create(['level' => $level]);
    foreach ($perms as $name) {
        $role->permissions()->attach(Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $name, 'module' => explode('.', $name)[0]],
        ));
    }
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

/**
 * @param  list<string>  $perms
 */
function roleWith(array $perms, int $level = 10): Role
{
    $role = Role::factory()->create(['level' => $level]);
    foreach ($perms as $name) {
        $role->permissions()->attach(Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $name, 'module' => explode('.', $name)[0]],
        ));
    }

    return $role;
}

it('crée un utilisateur avec e-mail et mot de passe, connectable immédiatement', function () {
    $admin = actorWith(['user.create', 'user.assign_role']);
    $role = roleWith([]);
    $warehouse = Warehouse::factory()->create();

    $response = $this->actingAs($admin)->postJson('/api/v1/users', [
        'name' => 'Nouveau Vendeur',
        'email' => 'vendeur@igoutech.ma',
        'password' => 'MotDePasse!123',
        'role_ids' => [$role->id],
        'warehouse_id' => $warehouse->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'vendeur@igoutech.ma');

    $this->assertDatabaseHas('users', ['email' => 'vendeur@igoutech.ma', 'is_active' => true]);

    $created = User::where('email', 'vendeur@igoutech.ma')->firstOrFail();
    expect(Hash::check('MotDePasse!123', $created->password))->toBeTrue();
});

it('refuse la création sans mot de passe', function () {
    $admin = actorWith(['user.create', 'user.assign_role']);
    $role = roleWith([]);
    $warehouse = Warehouse::factory()->create();

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name' => 'Sans Mot de Passe',
        'email' => 'sanspwd@igoutech.ma',
        'role_ids' => [$role->id],
        'warehouse_id' => $warehouse->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['password']);
});

it('permet à l\'admin de modifier le mot de passe d\'un utilisateur', function () {
    $admin = actorWith(['user.update']);
    $target = User::factory()->create();
    $target->createToken('mobile');

    $this->actingAs($admin)->putJson("/api/v1/users/{$target->id}/password", [
        'password' => 'NouveauPass!456',
        'password_confirmation' => 'NouveauPass!456',
    ])->assertOk();

    expect(Hash::check('NouveauPass!456', $target->fresh()->password))->toBeTrue()
        ->and($target->tokens()->count())->toBe(0);
});

it('exige un lieu pour un utilisateur sans accès global', function () {
    $admin = actorWith(['user.create', 'user.assign_role']);
    $role = roleWith(['stock.view']); // pas de stock.view_global

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name' => 'Sans Lieu',
        'email' => 'sanslieu@igoutech.ma',
        'password' => 'MotDePasse!123',
        'role_ids' => [$role->id],
    ])->assertStatus(422);
});

it('dispense de lieu un rôle à accès global', function () {
    $admin = actorWith(['user.create', 'user.assign_role']);
    $role = roleWith(['stock.view_global']);

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name' => 'Direction',
        'email' => 'direction@igoutech.ma',
        'password' => 'MotDePasse!123',
        'role_ids' => [$role->id],
    ])->assertCreated();
});

it('refuse qu\'un utilisateur se désactive lui-même', function () {
    $admin = actorWith(['user.deactivate']);

    $this->actingAs($admin)->patchJson("/api/v1/users/{$admin->id}/toggle")
        ->assertStatus(422);

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
});

it('refuse de désactiver le dernier administrateur actif', function () {
    $manager = actorWith(['user.deactivate'], 50);
    $lastAdmin = User::factory()->create();
    $lastAdmin->roles()->attach(roleWith(['role.manage'], 100));

    $this->actingAs($manager)->patchJson("/api/v1/users/{$lastAdmin->id}/toggle")
        ->assertStatus(422);

    $this->assertDatabaseHas('users', ['id' => $lastAdmin->id, 'is_active' => true]);
});

it('interdit d\'attribuer un rôle de rang supérieur au sien', function () {
    $manager = actorWith(['user.create', 'user.assign_role'], 50);
    $adminRole = roleWith(['role.manage'], 100);
    $warehouse = Warehouse::factory()->create();

    $this->actingAs($manager)->postJson('/api/v1/users', [
        'name' => 'Tentative Admin',
        'email' => 'tentative@igoutech.ma',
        'password' => 'MotDePasse!123',
        'role_ids' => [$adminRole->id],
        'warehouse_id' => $warehouse->id,
    ])->assertStatus(422);
});

it('empêche un utilisateur de modifier ses propres rôles', function () {
    $admin = actorWith(['user.assign_role']);
    $other = roleWith([]);

    $this->actingAs($admin)->putJson("/api/v1/users/{$admin->id}/roles", [
        'role_ids' => [$other->id],
    ])->assertForbidden();
});

it('désactive un utilisateur et révoque ses jetons', function () {
    $admin = actorWith(['user.deactivate']);
    $target = User::factory()->create(['is_active' => true]);
    $target->createToken('mobile');

    $this->actingAs($admin)->patchJson("/api/v1/users/{$target->id}/toggle")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($target->tokens()->count())->toBe(0);
});
