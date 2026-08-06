<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;

it('crée un client avec code auto-généré et plafond de crédit', function (): void {
    // Le plafond saisi dès la création exige la permission dédiée
    // « customer.set_credit_limit » (cf. CustomerTest).
    $user = grantUser(['customer.create', 'customer.view', 'customer.set_credit_limit']);

    $response = $this->actingAs($user)->postJson('/api/v1/customers', [
        'name' => 'Client Test',
        'phone' => '0600000000',
        'credit_limit' => 5000,
    ])->assertCreated();

    expect($response->json('data.code'))->toStartWith('CL-')
        ->and((float) $response->json('data.credit_limit'))->toBe(5000.0);

    $this->assertDatabaseHas('customers', [
        'name' => 'Client Test',
        'created_by' => $user->id,
    ]);
});

it('sans view_all, chacun ne voit que ses propres clients', function (): void {
    $userA = grantUser(['customer.create', 'customer.view']);
    $userB = grantUser(['customer.create', 'customer.view']);

    Customer::factory()->create(['name' => 'Client de A', 'created_by' => $userA->id]);
    Customer::factory()->create(['name' => 'Client de B', 'created_by' => $userB->id]);

    $response = $this->actingAs($userA)->getJson('/api/v1/customers')->assertOk();

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Client de A')
        ->and($names)->not->toContain('Client de B');
});

it('avec customer.view_all, on voit tous les clients', function (): void {
    $admin = grantUser(['customer.view', 'customer.view_all']);
    $other = grantUser(['customer.create']);

    Customer::factory()->create(['name' => 'Client de X', 'created_by' => $other->id]);

    $response = $this->actingAs($admin)->getJson('/api/v1/customers')->assertOk();

    expect(collect($response->json('data'))->pluck('name'))->toContain('Client de X');
});

it('refuse le détail d\'un client créé par un autre utilisateur', function (): void {
    $userA = grantUser(['customer.view']);
    $userB = grantUser(['customer.create']);

    $customer = Customer::factory()->create(['created_by' => $userB->id]);

    $this->actingAs($userA)->getJson("/api/v1/customers/{$customer->id}")->assertForbidden();
});

it('autorise le détail de son propre client', function (): void {
    $user = grantUser(['customer.view']);
    $customer = Customer::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)->getJson("/api/v1/customers/{$customer->id}")->assertOk();
});
