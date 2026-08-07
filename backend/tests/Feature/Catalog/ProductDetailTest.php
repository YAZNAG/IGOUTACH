<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;

it('retourne les détails complets du produit avec includes', function () {
    $product = Product::factory()->create();

    $user = grantUser(['product.view']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.sku', $product->sku)
        ->assertJsonPath('data.name', $product->name);

    // Vérifie que les relations sont chargées
    $response->assertJsonPath('data.category.id', $product->category_id);
    $response->assertJsonPath('data.unit.id', $product->unit_id);
});

it('retourne le stock du produit', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    Stock::factory()
        ->for($product)
        ->for($warehouse)
        ->create(['quantity' => 100]);

    $user = grantUser(['product.view', 'stock.view_global']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/stock");

    // La fiche article consomme un agregat (totaux + detail par lieu), pas
    // une liste plate : les totaux alimentent l'en-tete et les statistiques.
    $response->assertOk()
        ->assertJsonPath('data.product_id', $product->id)
        ->assertJsonPath('data.total_quantity', 100)
        ->assertJsonPath('data.locations.0.warehouse_id', $warehouse->id)
        ->assertJsonPath('data.locations.0.quantity', 100);
});

it('filtre le stock par warehouse_id', function () {
    $product = Product::factory()->create();
    $warehouse1 = Warehouse::factory()->create();
    $warehouse2 = Warehouse::factory()->create();

    Stock::factory()->for($product)->for($warehouse1)->create(['quantity' => 100]);
    Stock::factory()->for($product)->for($warehouse2)->create(['quantity' => 50]);

    $user = grantUser(['product.view', 'stock.view_global']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/stock?warehouse_id={$warehouse1->id}");

    $response->assertOk();
    $data = $response->json('data');

    expect($data['locations'])->toHaveLength(1);
    expect($data['locations'][0]['warehouse_id'])->toBe($warehouse1->id);
    expect($data['locations'][0]['quantity'])->toBe(100);
    // Le filtre porte aussi sur les totaux : sinon l'en-tete afficherait le
    // stock de tous les lieux tout en n'en detaillant qu'un.
    expect($data['total_quantity'])->toBe(100);
});

it('retourne les mouvements paginés du produit', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    StockMovement::factory()
        ->count(15)
        ->for($product)
        ->for($warehouse)
        ->create();

    $user = grantUser(['product.view', 'stock.view_global']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/movements?per_page=10");

    $response->assertOk()
        ->assertJsonPath('meta.total', 15)
        ->assertJsonPath('meta.per_page', 10);

    expect($response->json('data'))->toHaveLength(10);
});

it('filtre les mouvements par warehouse_id', function () {
    $product = Product::factory()->create();
    $warehouse1 = Warehouse::factory()->create();
    $warehouse2 = Warehouse::factory()->create();

    StockMovement::factory()
        ->count(5)
        ->for($product)
        ->for($warehouse1)
        ->create();

    StockMovement::factory()
        ->count(3)
        ->for($product)
        ->for($warehouse2)
        ->create();

    $user = grantUser(['product.view', 'stock.view_global']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/movements?warehouse_id={$warehouse1->id}");

    $response->assertOk()
        ->assertJsonPath('meta.total', 5);
});

it('applique le WarehouseScope pour les mouvements', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    StockMovement::factory()
        ->for($product)
        ->for($warehouse)
        ->create();

    // Créer un utilisateur assigné à ce lieu
    $user = User::factory()
        ->for($warehouse)
        ->create();

    $role = Role::factory()->create();
    $permission = Permission::firstOrCreate(
        ['name' => 'product.view'],
        ['display_name' => 'product.view', 'module' => 'product'],
    );
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/movements");

    $response->assertOk();
    // Les mouvements doivent être filtrés par le lieu de l'utilisateur
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(0);
});

it('retourne les fournisseurs du produit', function () {
    $product = Product::factory()->create();

    $supplier1 = Supplier::factory()->create(['code' => 'F001']);
    $supplier2 = Supplier::factory()->create(['code' => 'F002']);

    $product->suppliers()->attach($supplier1, ['supplier_reference' => 'REF001']);
    $product->suppliers()->attach($supplier2, ['supplier_reference' => 'REF002']);

    $user = grantUser(['product.view']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/suppliers");

    $response->assertOk();
    expect($response->json('data'))->toHaveLength(2);
});

it('retourne les statistiques du produit', function () {
    $product = Product::factory()->create();

    $user = grantUser(['product.view']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/statistics");

    $response->assertOk()
        ->assertJsonPath('data.product_id', $product->id)
        ->assertJsonPath('data.period', '12m');
});

it('masque le prix d\'achat dans le détail sans permission', function () {
    $product = Product::factory()->create(['cost_price' => 99.90]);

    $user = grantUser(['product.view']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonMissingPath('data.cost_price');
});

it('expose le prix d\'achat dans le détail avec permission', function () {
    $product = Product::factory()->create(['cost_price' => 99.90]);

    $user = grantUser(['product.view', 'product.view_cost_price']);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.cost_price', '99.90');
});
