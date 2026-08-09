<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\Inventory;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

function invProduct(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function invStock(int $warehouseId, int $productId, int $qty, float $avg = 10.0): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'average_cost' => (string) $avg,
    ]);
}

beforeEach(function () {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'adjustment'], ['name' => 'Ajustement', 'sign' => 0, 'affects_valuation' => false]);
});

it('ajoute du stock à un lieu via un bon d\'entrée daté', function () {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.entry'], ['warehouse_id' => $warehouse->id]);
    $product = invProduct();

    $this->actingAs($user)->postJson('/api/v1/stock/entry', [
        'warehouse_id' => $warehouse->id,
        'date' => '2026-07-15',
        'lines' => [['product_id' => $product->id, 'quantity' => 8, 'unit_cost' => 12.5]],
    ])->assertCreated();

    expect(app(StockReaderInterface::class)->quantityFor($warehouse->id, $product->id))->toBe(8);

    $this->assertDatabaseHas('stock_movements', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 8,
    ]);
});

it('crée un inventaire, calcule l\'écart et régularise à la validation', function () {
    // Le lieu doit exister avant l'utilisateur : celui-ci y est rattaché.
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['inventory.create', 'inventory.approve'], ['warehouse_id' => $warehouse->id]);
    $product = invProduct();
    invStock($warehouse->id, $product->id, 10);

    // Création (draft) avec date
    $res = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-07-20',
    ])->assertCreated();
    $inventoryId = $res->json('data.id');

    // Comptage : 7 comptés vs 10 théoriques → écart -3 (motif obligatoire)
    $this->actingAs($user)->putJson("/api/v1/inventories/{$inventoryId}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 7, 'reason' => 'Casse constatée']],
    ])->assertOk()->assertJsonPath('data.lines.0.difference', -3);

    // Validation → stock régularisé à 7
    $this->actingAs($user)->postJson("/api/v1/inventories/{$inventoryId}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect(app(StockReaderInterface::class)->quantityFor($warehouse->id, $product->id))->toBe(7);
});

it('régularise à la hausse quand le comptage dépasse le théorique', function () {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['inventory.create', 'inventory.approve'], ['warehouse_id' => $warehouse->id]);
    $product = invProduct();
    invStock($warehouse->id, $product->id, 5);

    $res = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-07-20',
    ]);
    $id = $res->json('data.id');

    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 9, 'reason' => 'Marchandise retrouvée']],
    ])->assertOk()->assertJsonPath('data.lines.0.difference', 4);

    $this->actingAs($user)->postJson("/api/v1/inventories/{$id}/approve")->assertOk();

    expect(app(StockReaderInterface::class)->quantityFor($warehouse->id, $product->id))->toBe(9);
});

it('refuse une nouvelle validation d\'un inventaire déjà validé', function () {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['inventory.create', 'inventory.approve'], ['warehouse_id' => $warehouse->id]);
    $inventory = Inventory::query()->create([
        'reference' => 'INV-TEST1',
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-07-20',
        'status' => Inventory::STATUS_APPROVED,
    ]);

    $this->actingAs($user)->postJson("/api/v1/inventories/{$inventory->id}/approve")
        ->assertStatus(422);
});
