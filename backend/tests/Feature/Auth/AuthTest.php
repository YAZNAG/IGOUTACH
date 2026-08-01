<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

// Requête « frontend » : Sanctum active la session stateful sur présence d'Origin.
$frontend = ['Origin' => 'http://localhost:5173'];

it('connecte un utilisateur avec des identifiants valides', function () use ($frontend) {
    $user = grantUser(['stock.view']);

    $this->withHeaders($frontend)->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertNoContent();

    $this->withHeaders($frontend)->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.permissions', ['stock.view']);
});

it('refuse des identifiants invalides', function () use ($frontend) {
    $user = grantUser();

    $this->withHeaders($frontend)->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'mauvais',
    ])->assertStatus(422);
});

it('interdit la vue globale sans stock.view_global', function () {
    $user = grantUser(['stock.view']);
    $this->actingAs($user);

    $this->getJson('/api/v1/dashboard')->assertForbidden();
});

it('expose la vue globale consolidée à un utilisateur autorisé', function () {
    $user = grantUser(['stock.view', 'stock.view_global']);
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 12,
        'average_cost' => '100',
    ]);

    $this->actingAs($user);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.summary.total_units', 12)
        ->assertJsonPath('data.stock.0.total_quantity', 12);
});
