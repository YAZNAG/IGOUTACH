<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;

it('refuse la création d\'un client sans la permission', function () {
    $user = grantUser(['customer.view']);

    $this->actingAs($user)
        ->postJson('/api/v1/customers', ['code' => 'C-9', 'name' => 'Test'])
        ->assertForbidden();
});

it('crée un client avec customer.create', function () {
    $user = grantUser(['customer.create']);

    $this->actingAs($user)->postJson('/api/v1/customers', [
        'code' => 'C-100',
        'name' => 'Électro Sud',
        'is_company' => true,
        'city' => 'Agadir',
    ])->assertCreated()->assertJsonPath('data.code', 'C-100');

    $this->assertDatabaseHas('customers', ['code' => 'C-100']);
});

it('refuse un code client en double', function () {
    $user = grantUser(['customer.create']);
    Customer::factory()->create(['code' => 'C-DUP']);

    $this->actingAs($user)
        ->postJson('/api/v1/customers', ['code' => 'C-DUP', 'name' => 'Autre'])
        ->assertStatus(422);
});

it('liste les clients avec customer.view', function () {
    $user = grantUser(['customer.view']);
    // Sans « customer.view_all », on ne voit que ses propres clients.
    Customer::factory()->count(3)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'credit_limit', 'available_credit', 'is_blocked']], 'meta']);
});

it('ne définit pas le plafond via la création (permission séparée)', function () {
    $user = grantUser(['customer.create']);

    $this->actingAs($user)->postJson('/api/v1/customers', [
        'code' => 'C-200',
        'name' => 'Test',
        'credit_limit' => 9999,
    ])->assertCreated();

    // credit_limit ignoré à la création : reste à 0.
    $this->assertDatabaseHas('customers', ['code' => 'C-200', 'credit_limit' => 0]);
});

it('définit le plafond de crédit avec la permission dédiée', function () {
    $user = grantUser(['customer.set_credit_limit']);
    $customer = Customer::factory()->create(['credit_limit' => 0]);

    $this->actingAs($user)->putJson("/api/v1/customers/{$customer->id}/credit", ['credit_limit' => 15000])
        ->assertOk()
        ->assertJsonPath('data.credit_limit', 15000);

    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'credit_limit' => 15000]);
});

it('refuse de définir le plafond sans la permission', function () {
    $user = grantUser(['customer.update']);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->putJson("/api/v1/customers/{$customer->id}/credit", ['credit_limit' => 15000])
        ->assertForbidden();
});

it('bloque et débloque un client', function () {
    $user = grantUser(['customer.set_credit_limit']);
    $customer = Customer::factory()->create(['is_blocked' => false]);

    $this->actingAs($user)->patchJson("/api/v1/customers/{$customer->id}/block")
        ->assertOk()
        ->assertJsonPath('data.is_blocked', true);

    $this->actingAs($user)->patchJson("/api/v1/customers/{$customer->id}/block")
        ->assertOk()
        ->assertJsonPath('data.is_blocked', false);
});

it('supprime un client avec customer.delete', function () {
    $user = grantUser(['customer.delete']);
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/customers/{$customer->id}")
        ->assertOk();

    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});
