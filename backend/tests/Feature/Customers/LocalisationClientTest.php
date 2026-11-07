<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Localisation facultative du client, posee depuis le telephone.
 */
it('pose puis retire la localisation d\'un client', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['customer.update', 'customer.view'], ['warehouse_id' => $lieu->id]);
    $client = Customer::factory()->create(['warehouse_id' => $lieu->id]);

    $this->actingAs($user)
        ->patchJson("/api/v1/customers/{$client->id}/location", ['latitude' => 30.4278, 'longitude' => -9.5981])
        ->assertOk()
        ->assertJsonPath('data.latitude', 30.4278)
        ->assertJsonPath('data.longitude', -9.5981);

    // La fiche complete la renvoie aussi : c'est elle que lit le telephone.
    $this->actingAs($user)->getJson("/api/v1/customers/{$client->id}/overview")
        ->assertOk()
        ->assertJsonPath('data.customer.latitude', 30.4278);

    $this->actingAs($user)
        ->patchJson("/api/v1/customers/{$client->id}/location", ['latitude' => null, 'longitude' => null])
        ->assertOk()
        ->assertJsonPath('data.latitude', null);

    expect($client->refresh()->latitude)->toBeNull();
});

it('refuse une coordonnee seule ou hors bornes', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['customer.update'], ['warehouse_id' => $lieu->id]);
    $client = Customer::factory()->create(['warehouse_id' => $lieu->id]);

    $this->actingAs($user)
        ->patchJson("/api/v1/customers/{$client->id}/location", ['latitude' => 30.4, 'longitude' => null])
        ->assertStatus(422);

    $this->actingAs($user)
        ->patchJson("/api/v1/customers/{$client->id}/location", ['latitude' => 95, 'longitude' => 10])
        ->assertStatus(422);
});

it('ne localise pas le client d\'un autre lieu', function (): void {
    $mien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $user = grantUser(['customer.update'], ['warehouse_id' => $mien->id]);
    $client = Customer::factory()->create(['warehouse_id' => $autre->id]);

    $this->actingAs($user)
        ->patchJson("/api/v1/customers/{$client->id}/location", ['latitude' => 30.4, 'longitude' => -9.6])
        ->assertForbidden();
});
