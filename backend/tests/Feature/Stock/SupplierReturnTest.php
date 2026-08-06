<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'return_out'], ['name' => 'Retour fournisseur', 'sign' => -1, 'affects_valuation' => false]);
});

function supRetProduct(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

it('renvoie plusieurs articles au fournisseur en une transaction', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['purchase.return'], ['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create();

    $a = supRetProduct();
    $b = supRetProduct();
    foreach ([$a, $b] as $p) {
        Stock::withoutGlobalScopes()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $p->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
            'average_cost' => '10.00',
        ]);
    }

    $this->actingAs($user)->postJson('/api/v1/stock/supplier-return', [
        'warehouse_id' => $warehouse->id,
        'supplier_id' => $supplier->id,
        'reason' => 'Marchandise défectueuse',
        'lines' => [
            ['product_id' => $a->id, 'quantity' => 5],
            ['product_id' => $b->id, 'quantity' => 3],
        ],
    ])->assertCreated()->assertJsonPath('lines_count', 2);

    $reader = app(StockReaderInterface::class);
    expect($reader->quantityFor($warehouse->id, $a->id))->toBe(15)
        ->and($reader->quantityFor($warehouse->id, $b->id))->toBe(17);
});

it('exige un motif de renvoi', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['purchase.return'], ['warehouse_id' => $warehouse->id]);
    $product = supRetProduct();

    $this->actingAs($user)->postJson('/api/v1/stock/supplier-return', [
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertStatus(422);
});

it('refuse un renvoi supérieur au stock disponible, sans rien appliquer', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['purchase.return'], ['warehouse_id' => $warehouse->id]);

    $a = supRetProduct();
    $b = supRetProduct();
    Stock::withoutGlobalScopes()->create(['warehouse_id' => $warehouse->id, 'product_id' => $a->id, 'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '10.00']);
    Stock::withoutGlobalScopes()->create(['warehouse_id' => $warehouse->id, 'product_id' => $b->id, 'quantity' => 2, 'reserved_quantity' => 0, 'average_cost' => '10.00']);

    $this->actingAs($user)->postJson('/api/v1/stock/supplier-return', [
        'warehouse_id' => $warehouse->id,
        'reason' => 'Excédent',
        'lines' => [
            ['product_id' => $a->id, 'quantity' => 4],
            ['product_id' => $b->id, 'quantity' => 99], // dépasse le stock
        ],
    ])->assertStatus(422);

    // La première ligne ne doit pas avoir été appliquée.
    expect(app(StockReaderInterface::class)->quantityFor($warehouse->id, $a->id))->toBe(10);
});
