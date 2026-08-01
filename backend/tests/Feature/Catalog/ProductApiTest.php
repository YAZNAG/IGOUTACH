<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Models\User;

/**
 * @param  list<string>  $permissionNames
 */
function actingUserWith(array $permissionNames): User
{
    $role = Role::factory()->create();

    foreach ($permissionNames as $name) {
        $permission = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $name, 'module' => explode('.', $name)[0]],
        );
        $role->permissions()->attach($permission);
    }

    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

it('refuse la création d\'un article sans la permission', function () {
    $user = actingUserWith(['product.view']);

    $response = $this->actingAs($user)->postJson('/api/v1/products', []);

    $response->assertForbidden();
});

it('crée un article avec la permission product.create', function () {
    $user = actingUserWith(['product.create']);
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/products', [
        'sku' => 'SKU-0001',
        'name' => 'Clavier mécanique',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'cost_price' => 120,
        'sale_price' => 250,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.sku', 'SKU-0001');

    $this->assertDatabaseHas('products', ['sku' => 'SKU-0001']);
});

it('masque le prix d\'achat sans product.view_cost_price', function () {
    $user = actingUserWith(['product.view']);
    $product = Product::factory()->create(['cost_price' => 99.90]);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonMissingPath('data.cost_price');
});

it('expose le prix d\'achat avec product.view_cost_price', function () {
    $user = actingUserWith(['product.view', 'product.view_cost_price']);
    $product = Product::factory()->create(['cost_price' => 99.90]);

    $response = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.cost_price', '99.90');
});
