<?php

declare(strict_types=1);

use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Warehouses\Models\WarehouseType;

it('refuse la création d\'un lieu sans la permission', function () {
    $user = grantUser(['warehouse.view']);

    $this->actingAs($user)
        ->postJson('/api/v1/warehouses', [])
        ->assertForbidden();
});

it('crée un lieu avec la permission warehouse.create', function () {
    $user = grantUser(['warehouse.create']);
    $type = WarehouseType::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/warehouses', [
        'code' => 'VEH-09',
        'name' => 'Véhicule test',
        'warehouse_type_id' => $type->id,
        'city' => 'Casablanca',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.code', 'VEH-09');

    $this->assertDatabaseHas('warehouses', ['code' => 'VEH-09']);
});

it('liste les lieux avec warehouse.view', function () {
    $user = grantUser(['warehouse.view']);
    Warehouse::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/warehouses')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('liste les types de lieux', function () {
    $user = grantUser(['warehouse.view']);
    WarehouseType::factory()->create(['code' => 'depot', 'name' => 'Dépôt']);

    $this->actingAs($user)
        ->getJson('/api/v1/warehouse-types')
        ->assertOk()
        ->assertJsonPath('data.0.code', 'depot');
});

it('met à jour un lieu avec warehouse.update', function () {
    $user = grantUser(['warehouse.update']);
    $warehouse = Warehouse::factory()->create(['name' => 'Ancien nom']);

    $this->actingAs($user)->putJson("/api/v1/warehouses/{$warehouse->id}", [
        'code' => $warehouse->code,
        'name' => 'Nouveau nom',
        'warehouse_type_id' => $warehouse->warehouse_type_id,
    ])->assertOk()->assertJsonPath('data.name', 'Nouveau nom');
});
