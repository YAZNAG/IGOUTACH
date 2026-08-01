<?php

declare(strict_types=1);

use App\Domain\Purchasing\Models\Supplier;

it('refuse la création d\'un fournisseur sans la permission', function () {
    $user = grantUser(['supplier.view']);

    $this->actingAs($user)
        ->postJson('/api/v1/suppliers', ['code' => 'F-9', 'name' => 'Test'])
        ->assertForbidden();
});

it('crée un fournisseur avec supplier.create', function () {
    $user = grantUser(['supplier.create']);

    $this->actingAs($user)->postJson('/api/v1/suppliers', [
        'code' => 'F-100',
        'name' => 'Global Import',
        'city' => 'Casablanca',
        'payment_terms_days' => 30,
    ])->assertCreated()->assertJsonPath('data.code', 'F-100');

    $this->assertDatabaseHas('suppliers', ['code' => 'F-100', 'name' => 'Global Import']);
});

it('refuse un code fournisseur en double', function () {
    $user = grantUser(['supplier.create']);
    Supplier::factory()->create(['code' => 'F-DUP']);

    $this->actingAs($user)
        ->postJson('/api/v1/suppliers', ['code' => 'F-DUP', 'name' => 'Autre'])
        ->assertStatus(422);
});

it('liste les fournisseurs avec supplier.view', function () {
    $user = grantUser(['supplier.view']);
    Supplier::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/suppliers')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'payment_terms_days']], 'meta']);
});

it('met à jour un fournisseur avec supplier.update', function () {
    $user = grantUser(['supplier.update']);
    $supplier = Supplier::factory()->create(['name' => 'Ancien']);

    $this->actingAs($user)->putJson("/api/v1/suppliers/{$supplier->id}", [
        'code' => $supplier->code,
        'name' => 'Nouveau',
        'payment_terms_days' => 60,
    ])->assertOk()->assertJsonPath('data.name', 'Nouveau');
});

it('supprime un fournisseur avec supplier.delete', function () {
    $user = grantUser(['supplier.delete']);
    $supplier = Supplier::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/suppliers/{$supplier->id}")
        ->assertOk();

    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
});
