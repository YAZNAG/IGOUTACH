<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

function seedStockRow(int $warehouseId, int $productId, int $qty, float $avg = 10.0): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'average_cost' => (string) $avg,
    ]);
}

function makeProduct(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

beforeEach(function () {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

it('crée un bon de sortie qui décrémente le stock et journalise', function () {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.issue', 'stock.view'], ['warehouse_id' => $warehouse->id]);
    $product = makeProduct();
    seedStockRow($warehouse->id, $product->id, 10);

    $this->actingAs($user)->postJson('/api/v1/stock/issue', [
        'warehouse_id' => $warehouse->id,
        'reason_code' => 'breakage',
        'lines' => [['product_id' => $product->id, 'quantity' => 3, 'note' => 'Écran cassé']],
    ])->assertCreated();

    expect((int) Stock::withoutGlobalScopes()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity'))->toBe(7);

    $this->assertDatabaseHas('stock_movements', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => -3,
    ]);
});

it('refuse un bon de sortie si le stock est insuffisant', function () {
    $user = grantUser(['stock.issue']);
    $warehouse = Warehouse::factory()->create();
    $product = makeProduct();
    seedStockRow($warehouse->id, $product->id, 2);

    $this->actingAs($user)->postJson('/api/v1/stock/issue', [
        'warehouse_id' => $warehouse->id,
        'reason_code' => 'loss',
        'lines' => [['product_id' => $product->id, 'quantity' => 5]],
    ])->assertStatus(422);

    expect((int) Stock::withoutGlobalScopes()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity'))->toBe(2);
});

it('refuse le bon de sortie sans la permission', function () {
    $user = grantUser(['stock.view']);
    $warehouse = Warehouse::factory()->create();
    $product = makeProduct();

    $this->actingAs($user)->postJson('/api/v1/stock/issue', [
        'warehouse_id' => $warehouse->id,
        'reason_code' => 'loss',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertForbidden();
});

it('liste le stock d\'un lieu avec statut', function () {
    // Le lieu doit exister avant l'utilisateur : celui-ci y est rattaché.
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.view'], ['warehouse_id' => $warehouse->id]);
    $product = makeProduct();
    seedStockRow($warehouse->id, $product->id, 5);

    $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$warehouse->id}")
        ->assertOk()
        ->assertJsonPath('data.0.quantity', 5)
        ->assertJsonPath('data.0.status', 'ok');
});

it('expose le journal des mouvements après un bon de sortie', function () {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.issue', 'stock.view'], ['warehouse_id' => $warehouse->id]);
    $product = makeProduct();
    seedStockRow($warehouse->id, $product->id, 10);

    $this->actingAs($user)->postJson('/api/v1/stock/issue', [
        'warehouse_id' => $warehouse->id,
        'reason_code' => 'internal_use',
        'lines' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertCreated();

    $this->actingAs($user)
        ->getJson("/api/v1/stock/movements?warehouse_id={$warehouse->id}")
        ->assertOk()
        ->assertJsonPath('data.0.quantity', -2)
        ->assertJsonPath('data.0.type_code', 'out');
});
