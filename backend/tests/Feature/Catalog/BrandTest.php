<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;

it('liste les marques avec brand.view', function () {
    Brand::factory()->create(['name' => 'ASTRO', 'code' => 'ASTRO']);

    $this->actingAs(grantUser(['brand.view']))
        ->getJson('/api/v1/brands')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'code', 'logo_url', 'products_count']]]);
});

it('crée une marque avec brand.manage', function () {
    $this->actingAs(grantUser(['brand.manage']))
        ->postJson('/api/v1/brands', ['name' => 'ECHOLINK', 'code' => 'ECHOLINK'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'ECHOLINK');
});

it('bloque la suppression d\'une marque utilisée', function () {
    $brand = Brand::factory()->create(['name' => 'ASTRO', 'code' => 'ASTRO']);
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id, 'category_id' => $category->id, 'unit_id' => $unit->id]);

    $this->actingAs(grantUser(['brand.manage']))
        ->deleteJson("/api/v1/brands/{$brand->id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('brands', ['id' => $brand->id, 'is_active' => true]);
});

it('désactive une marque non utilisée', function () {
    $brand = Brand::factory()->create(['name' => 'LOZA', 'code' => 'LOZA']);

    $this->actingAs(grantUser(['brand.manage']))
        ->deleteJson("/api/v1/brands/{$brand->id}")
        ->assertOk();

    $this->assertDatabaseHas('brands', ['id' => $brand->id, 'is_active' => false]);
});

it('détecte les marques en dry-run sans rien écrire, puis applique', function () {
    $brand = Brand::factory()->create(['name' => 'ASTRO', 'code' => 'ASTRO']);
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();
    $product = Product::factory()->create([
        'name' => 'RECEPTEUR ASTRO 200 HD',
        'brand_id' => null,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    // dry-run : n'écrit rien
    $this->artisan('catalog:detect-brands')->assertSuccessful();
    expect($product->refresh()->brand_id)->toBeNull();

    // apply : rattache la détection sûre
    $this->artisan('catalog:detect-brands --apply')->assertSuccessful();
    expect($product->refresh()->brand_id)->toBe($brand->id);
});
