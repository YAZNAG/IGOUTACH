<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Actions\StockInAction;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;
use Database\Seeders\PriceTypeSeeder;
use Illuminate\Http\UploadedFile;

it('refuse de supprimer une catégorie contenant des articles', function () {
    $user = grantUser(['category.delete']);
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/categories/{$category->id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('supprime une catégorie vide', function () {
    $user = grantUser(['category.delete']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/categories/{$category->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('refuse de supprimer un article ayant des mouvements de stock', function () {
    $this->seed(MovementTypeSeeder::class);
    $user = grantUser(['product.delete']);
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    app(StockInAction::class)->execute(
        new StockMovementData($warehouse->id, $product->id, 5, MovementType::IN, 100),
    );

    $this->actingAs($user)
        ->deleteJson("/api/v1/products/{$product->id}")
        ->assertStatus(422);
});

it('supprime (soft) un article non engagé', function () {
    $user = grantUser(['product.delete']);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/products/{$product->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

it('importe des articles depuis un CSV', function () {
    $this->seed(PriceTypeSeeder::class);
    $user = grantUser(['product.import']);

    $csv = "Nom,Référence,Prix d'achat,Prix de vente,Alerte Stock Minimal,TVA,Catégorie,Description\n"
        ."CABLE HDMI 2M,HDMI-2M,20,35,5,0,CABLES,Cable HDMI\n"
        ."SOURIS USB,SOURIS-USB,15,30,8,0,ACCESSOIRES,Souris filaire\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

    $this->actingAs($user)
        ->post('/api/v1/products/import', ['file' => $file])
        ->assertOk()
        ->assertJsonPath('data.created', 2);

    $this->assertDatabaseHas('products', ['sku' => 'HDMI-2M', 'name' => 'CABLE HDMI 2M']);
    $this->assertDatabaseHas('categories', ['name' => 'CABLES']);
});

it('supprime en masse les catégories vides et bloque celles utilisées', function () {
    $user = grantUser(['category.delete']);
    $empty1 = Category::factory()->create();
    $empty2 = Category::factory()->create();
    $used = Category::factory()->create();
    Product::factory()->create(['category_id' => $used->id]);

    $this->actingAs($user)
        ->postJson('/api/v1/categories/bulk-delete', ['ids' => [$empty1->id, $empty2->id, $used->id]])
        ->assertOk()
        ->assertJsonPath('data.deleted', 2)
        ->assertJsonPath('data.blocked.0.id', $used->id);

    $this->assertDatabaseMissing('categories', ['id' => $empty1->id]);
    $this->assertDatabaseHas('categories', ['id' => $used->id]);
});

it('supprime en masse les articles non engagés', function () {
    $user = grantUser(['product.delete']);
    $a = Product::factory()->create();
    $b = Product::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/products/bulk-delete', ['ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertJsonPath('data.deleted', 2);

    $this->assertSoftDeleted('products', ['id' => $a->id]);
});

it('refuse la suppression sans permission', function () {
    $user = grantUser(['category.view']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/categories/{$category->id}")
        ->assertForbidden();
});
