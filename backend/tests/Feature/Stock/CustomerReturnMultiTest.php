<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'return_in'], ['name' => 'Retour client', 'sign' => 1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

function returnProduct(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function returnStock(int $warehouseId, int $productId, int $qty): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);
}

it('enregistre un retour client de plusieurs articles en une fois', function (): void {
    $user = grantUser(['stock.entry']);
    $warehouse = Warehouse::factory()->create();
    $a = returnProduct();
    $b = returnProduct();
    returnStock($warehouse->id, $a->id, 5);
    returnStock($warehouse->id, $b->id, 5);

    $this->actingAs($user)->postJson('/api/v1/stock/return-multi', [
        'warehouse_id' => $warehouse->id,
        'note' => 'Retour du 05/08/2026',
        'lines' => [
            ['product_id' => $a->id, 'quantity' => 3, 'condition' => 'resellable'],
            ['product_id' => $b->id, 'quantity' => 2, 'condition' => 'defective'],
        ],
    ])->assertCreated()->assertJsonPath('lines_count', 2);

    $reader = app(StockReaderInterface::class);
    // Revendable : +3. Défectueux : +2 puis −2 (sortie SAV) = inchangé.
    expect($reader->quantityFor($warehouse->id, $a->id))->toBe(8)
        ->and($reader->quantityFor($warehouse->id, $b->id))->toBe(5);
});

it('n\'enregistre aucune ligne si une seule est invalide', function (): void {
    $user = grantUser(['stock.entry']);
    $warehouse = Warehouse::factory()->create();
    $a = returnProduct();
    returnStock($warehouse->id, $a->id, 5);

    // Deuxième ligne invalide (article inexistant) : tout est refusé en bloc,
    // la première ligne ne doit pas avoir été appliquée.
    $this->actingAs($user)->postJson('/api/v1/stock/return-multi', [
        'warehouse_id' => $warehouse->id,
        'lines' => [
            ['product_id' => $a->id, 'quantity' => 3, 'condition' => 'resellable'],
            ['product_id' => 999999, 'quantity' => 2, 'condition' => 'resellable'],
        ],
    ])->assertStatus(422);

    expect(app(StockReaderInterface::class)->quantityFor($warehouse->id, $a->id))->toBe(5);
});

it('refuse un retour sans ligne', function (): void {
    $user = grantUser(['stock.entry']);
    $warehouse = Warehouse::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/stock/return-multi', [
        'warehouse_id' => $warehouse->id,
        'lines' => [],
    ])->assertStatus(422);
});
