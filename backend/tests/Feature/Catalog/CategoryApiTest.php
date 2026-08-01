<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

it('refuse la création d\'une catégorie sans la permission', function () {
    $user = grantUser(['category.view']);

    $this->actingAs($user)
        ->postJson('/api/v1/categories', ['name' => 'RESEAUX'])
        ->assertForbidden();
});

it('crée une catégorie avec category.create', function () {
    $user = grantUser(['category.create']);

    $this->actingAs($user)
        ->postJson('/api/v1/categories', ['name' => 'RESEAUX'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'RESEAUX');

    $this->assertDatabaseHas('categories', ['name' => 'RESEAUX']);
});

it('liste les catégories avec le nombre d\'articles', function () {
    $user = grantUser(['category.view']);
    $category = Category::factory()->create();
    Product::factory()->count(2)->create(['category_id' => $category->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.products_count', 2);
});

it('crée un article avec les infos fixes seulement (prix et unité par défaut)', function () {
    $user = grantUser(['product.create']);
    $category = Category::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/products', [
        'sku' => 'RJ45-5M',
        'name' => 'CABLE RJ45 5M CAT6',
        'category_id' => $category->id,
        'min_stock' => 10,
    ]);

    $response->assertCreated()->assertJsonPath('data.sku', 'RJ45-5M');
    $this->assertDatabaseHas('products', ['sku' => 'RJ45-5M', 'sale_price' => 0]);
});

it('paramètre le tarif de vente avec product.set_price', function () {
    $user = grantUser(['product.view', 'product.view_cost_price', 'product.set_price']);
    $product = Product::factory()->create(['sale_price' => 0]);

    $this->actingAs($user)
        ->putJson("/api/v1/products/{$product->id}/pricing", ['sale_price' => 55, 'cost_price' => 40])
        ->assertOk()
        ->assertJsonPath('data.sale_price', '55.00');

    $this->assertDatabaseHas('products', ['id' => $product->id, 'sale_price' => 55]);
});

it('refuse la tarification sans product.set_price', function () {
    $user = grantUser(['product.view']);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->putJson("/api/v1/products/{$product->id}/pricing", ['sale_price' => 55])
        ->assertForbidden();
});
