<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Warehouses\Models\WarehouseType;
use App\Models\User;

/**
 * @param  list<string>  $perms
 */
function whActor(array $perms, int $level = 100): User
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

function whRole(int $level = 10): Role
{
    return Role::factory()->create(['level' => $level]);
}

function vehicleWarehouse(): Warehouse
{
    $type = WarehouseType::factory()->create([
        'code' => 'vehicle',
        'name' => 'Véhicule',
        'allows_sales' => true,
        'allows_purchase_receipt' => false,
        'requires_transfer_approval' => true,
    ]);

    return Warehouse::factory()->create(['warehouse_type_id' => $type->id]);
}

it('le type véhicule interdit la réception fournisseur', function () {
    $vehicle = vehicleWarehouse();

    expect($vehicle->type->allows_purchase_receipt)->toBeFalse();
    expect($vehicle->isVehicle())->toBeTrue();
});

it('refuse un second vendeur sur un véhicule', function () {
    $admin = whActor(['user.create', 'user.assign_role']);
    $vehicle = vehicleWarehouse();
    $role = whRole();

    // Premier vendeur rattaché.
    User::factory()->create(['warehouse_id' => $vehicle->id]);

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name' => 'Vendeur B',
        'email' => 'vendeurb@igoutech.ma',
        'password' => 'MotDePasse!123',
        'role_ids' => [$role->id],
        'warehouse_id' => $vehicle->id,
    ])->assertStatus(422);
});

it('accepte le premier vendeur d\'un véhicule', function () {
    $admin = whActor(['user.create', 'user.assign_role']);
    $vehicle = vehicleWarehouse();
    $role = whRole();

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name' => 'Vendeur A',
        'email' => 'vendeura@igoutech.ma',
        'password' => 'MotDePasse!123',
        'role_ids' => [$role->id],
        'warehouse_id' => $vehicle->id,
    ])->assertCreated();
});

it('refuse de désactiver un lieu au stock non nul', function () {
    $admin = whActor(['warehouse.manage']);
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    Stock::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'average_cost' => 10,
    ]);

    $this->actingAs($admin)->patchJson("/api/v1/warehouses/{$warehouse->id}/toggle")
        ->assertStatus(422);

    $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'is_active' => true]);
});

it('désactive un lieu vide', function () {
    $admin = whActor(['warehouse.manage']);
    $warehouse = Warehouse::factory()->create(['is_active' => true]);

    $this->actingAs($admin)->patchJson("/api/v1/warehouses/{$warehouse->id}/toggle")
        ->assertOk();

    $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'is_active' => false]);
});
