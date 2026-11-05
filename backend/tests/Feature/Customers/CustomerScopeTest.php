<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Le fichier client est separe par lieu : chaque fiche appartient a un lieu,
 * un responsable ne voit que celles de son lieu, la direction voit tout.
 */
it('crée un client avec code auto-généré et plafond de crédit, rattaché au lieu du responsable', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['customer.create', 'customer.view', 'customer.set_credit_limit'], ['warehouse_id' => $lieu->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/customers', [
        'name' => 'Client Test',
        'phone' => '0600000000',
        'credit_limit' => 5000,
    ])->assertCreated();

    expect($response->json('data.code'))->toStartWith('CL-')
        ->and((float) $response->json('data.credit_limit'))->toBe(5000.0)
        ->and($response->json('data.warehouse_id'))->toBe($lieu->id);

    $this->assertDatabaseHas('customers', [
        'name' => 'Client Test',
        'created_by' => $user->id,
        'warehouse_id' => $lieu->id,
    ]);
});

it('un responsable ne peut pas créer une fiche dans un autre lieu que le sien', function (): void {
    $mien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $user = grantUser(['customer.create'], ['warehouse_id' => $mien->id]);

    // Le lieu envoye est ignore : la fiche va dans le lieu du responsable.
    $this->actingAs($user)->postJson('/api/v1/customers', [
        'name' => 'Client glisse',
        'warehouse_id' => $autre->id,
    ])->assertCreated()->assertJsonPath('data.warehouse_id', $mien->id);
});

it('la direction doit choisir le lieu de la fiche', function (): void {
    $admin = grantUser(['customer.create', 'customer.view_all']);

    $this->actingAs($admin)->postJson('/api/v1/customers', ['name' => 'Sans lieu'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id']);

    $lieu = Warehouse::factory()->create();
    $this->actingAs($admin)->postJson('/api/v1/customers', ['name' => 'Avec lieu', 'warehouse_id' => $lieu->id])
        ->assertCreated()->assertJsonPath('data.warehouse_id', $lieu->id);
});

it('sans vue globale, chacun ne voit que les clients de son lieu', function (): void {
    $lieuA = Warehouse::factory()->create();
    $lieuB = Warehouse::factory()->create();
    $userA = grantUser(['customer.view'], ['warehouse_id' => $lieuA->id]);

    Customer::factory()->create(['name' => 'Client de A', 'warehouse_id' => $lieuA->id]);
    Customer::factory()->create(['name' => 'Client de B', 'warehouse_id' => $lieuB->id]);

    $response = $this->actingAs($userA)->getJson('/api/v1/customers')->assertOk();

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Client de A')
        ->and($names)->not->toContain('Client de B');
});

it('avec customer.view_all, on voit tous les clients et on peut filtrer par lieu', function (): void {
    $lieuA = Warehouse::factory()->create();
    $lieuB = Warehouse::factory()->create();
    $admin = grantUser(['customer.view', 'customer.view_all']);

    Customer::factory()->create(['name' => 'Client de A', 'warehouse_id' => $lieuA->id]);
    Customer::factory()->create(['name' => 'Client de B', 'warehouse_id' => $lieuB->id]);

    $tous = collect($this->actingAs($admin)->getJson('/api/v1/customers')->assertOk()->json('data'))->pluck('name');
    expect($tous)->toContain('Client de A')->and($tous)->toContain('Client de B');

    $seulementB = collect($this->actingAs($admin)->getJson("/api/v1/customers?warehouse_id={$lieuB->id}")->assertOk()->json('data'))->pluck('name');
    expect($seulementB)->toContain('Client de B')->and($seulementB)->not->toContain('Client de A');
});

it('refuse le détail d\'un client d\'un autre lieu', function (): void {
    $mien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $user = grantUser(['customer.view'], ['warehouse_id' => $mien->id]);

    $customer = Customer::factory()->create(['warehouse_id' => $autre->id]);

    $this->actingAs($user)->getJson("/api/v1/customers/{$customer->id}")->assertForbidden();
});

it('autorise le détail d\'un client de son lieu', function (): void {
    $mien = Warehouse::factory()->create();
    $user = grantUser(['customer.view'], ['warehouse_id' => $mien->id]);
    $customer = Customer::factory()->create(['warehouse_id' => $mien->id]);

    $this->actingAs($user)->getJson("/api/v1/customers/{$customer->id}")->assertOk();
});
