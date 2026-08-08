<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Le coût moyen EST le prix d'achat. Toute voie qui l'expose contourne
 * « product.view_cost_price » : un responsable de lieu connaîtrait les marges
 * de l'entreprise en lisant simplement son écran de stock.
 */
function coutProduit(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'cost_price' => 80,
        'sale_price' => 120,
    ]);
}

it('masque le coût moyen dans la liste de stock sans la permission', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.view', 'product.view'], ['warehouse_id' => $warehouse->id]);
    $product = coutProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '80.00',
    ]);

    $ligne = collect($this->actingAs($user)->getJson('/api/v1/stock')->assertOk()->json('data'))
        ->firstWhere('product_id', $product->id);

    expect($ligne['quantity'])->toBe(10)
        ->and($ligne['average_cost'])->toBeNull()
        ->and($ligne['value'])->toBeNull();
});

it('montre le coût moyen à qui détient la permission', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.view', 'product.view', 'product.view_cost_price'], ['warehouse_id' => $warehouse->id]);
    $product = coutProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '80.00',
    ]);

    $ligne = collect($this->actingAs($user)->getJson('/api/v1/stock')->json('data'))
        ->firstWhere('product_id', $product->id);

    expect((float) $ligne['average_cost'])->toBe(80.0)
        ->and((float) $ligne['value'])->toBe(800.0);
});

it('masque la valorisation du stock d\'un article sans la permission', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.view', 'product.view'], ['warehouse_id' => $warehouse->id]);
    $product = coutProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '80.00',
    ]);

    $data = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/stock")->json('data');

    expect($data['total_quantity'])->toBe(10)
        ->and($data['total_valuation'])->toBeNull()
        ->and($data['locations'][0]['valuation'])->toBeNull();
});

it('masque la marge dans les statistiques sans la permission', function (): void {
    $user = grantUser(['product.view']);
    $product = coutProduit();

    $stats = $this->actingAs($user)
        ->getJson("/api/v1/products/{$product->id}/statistics")
        ->assertOk()
        ->json('data');

    // La marge permettrait de retrouver le coût par simple division.
    expect($stats)->not->toHaveKey('cost_of_goods')
        ->and($stats)->not->toHaveKey('gross_margin')
        ->and($stats)->not->toHaveKey('margin_percent')
        ->and($stats)->toHaveKey('revenue');
});

it('conserve la marge pour qui détient la permission', function (): void {
    $user = grantUser(['product.view', 'product.view_cost_price']);
    $product = coutProduit();

    $stats = $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/statistics")->json('data');

    expect($stats)->toHaveKey('gross_margin')->and($stats)->toHaveKey('margin_percent');
});
