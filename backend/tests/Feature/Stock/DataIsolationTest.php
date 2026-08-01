<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function () {
    $this->warehouseA = Warehouse::factory()->create();
    $this->warehouseB = Warehouse::factory()->create();
    $product = Product::factory()->create();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->warehouseA->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'average_cost' => '100',
    ]);
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->warehouseB->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'average_cost' => '100',
    ]);
});

it('n\'expose que le stock du lieu de l\'utilisateur restreint', function () {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->warehouseA->id]);
    $this->actingAs($user);

    $stocks = Stock::all();

    expect($stocks)->toHaveCount(1)
        ->and($stocks->first()->warehouse_id)->toBe($this->warehouseA->id);
});

it('expose tous les lieux avec la permission stock.view_global', function () {
    $user = grantUser(['stock.view', 'stock.view_global'], ['warehouse_id' => $this->warehouseA->id]);
    $this->actingAs($user);

    expect(Stock::all())->toHaveCount(2);
});

it('un utilisateur du lieu B ne voit pas le stock du lieu A', function () {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->warehouseB->id]);
    $this->actingAs($user);

    $stocks = Stock::all();

    expect($stocks)->toHaveCount(1)
        ->and($stocks->first()->warehouse_id)->toBe($this->warehouseB->id);
});

it('ignore le filtre en contexte console (aucun utilisateur)', function () {
    // Sans utilisateur authentifié, le scope ne filtre pas.
    expect(Stock::all())->toHaveCount(2);
});
