<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\Inventory;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Le cloisonnement doit valoir en ÉCRITURE autant qu'en lecture : un
 * responsable ne doit pas pouvoir agir sur le lieu d'un autre en changeant
 * simplement `warehouse_id` dans sa requête.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'adjustment'], ['name' => 'Ajustement', 'sign' => 0, 'affects_valuation' => false]);

    $this->mien = Warehouse::factory()->create(['code' => 'MIEN']);
    $this->autre = Warehouse::factory()->create(['code' => 'AUTRE']);
});

function gardeProduit(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

it('refuse de créer un inventaire sur le lieu d\'un autre', function (): void {
    $user = grantUser(['inventory.create'], ['warehouse_id' => $this->mien->id]);

    $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $this->autre->id,
        'counted_at' => '2026-08-09',
    ])->assertStatus(422)->assertJsonValidationErrors('warehouse_id');

    expect(Inventory::withoutGlobalScopes()->where('warehouse_id', $this->autre->id)->count())->toBe(0);
});

it('accepte l\'inventaire sur son propre lieu', function (): void {
    $user = grantUser(['inventory.create'], ['warehouse_id' => $this->mien->id]);

    $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $this->mien->id,
        'counted_at' => '2026-08-09',
    ])->assertCreated();
});

it('refuse une entrée de stock sur le lieu d\'un autre', function (): void {
    $user = grantUser(['stock.entry'], ['warehouse_id' => $this->mien->id]);
    $product = gardeProduit();

    $this->actingAs($user)->postJson('/api/v1/stock/entry', [
        'warehouse_id' => $this->autre->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_cost' => 5,
    ])->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
});

it('refuse une sortie de stock sur le lieu d\'un autre', function (): void {
    $user = grantUser(['stock.issue'], ['warehouse_id' => $this->mien->id]);
    $product = gardeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->autre->id, 'product_id' => $product->id,
        'quantity' => 50, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $this->actingAs($user)->postJson('/api/v1/stock/issue', [
        'warehouse_id' => $this->autre->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ])->assertStatus(422);

    // Le stock de l'autre lieu ne doit pas avoir bougé.
    expect((int) Stock::withoutGlobalScopes()
        ->where('warehouse_id', $this->autre->id)->where('product_id', $product->id)
        ->value('quantity'))->toBe(50);
});

it('refuse une vente rattachée au lieu d\'un autre', function (): void {
    $user = grantUser(['sale.create'], ['warehouse_id' => $this->mien->id]);
    $product = gardeProduit();

    $this->actingAs($user)->postJson('/api/v1/sales', [
        'warehouse_id' => $this->autre->id,
        'type' => 'invoice',
        'lines' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]],
    ])->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
});

it('refuse d\'ouvrir une caisse sur le lieu d\'un autre', function (): void {
    $user = grantUser(['cash.open', 'cash.manage'], ['warehouse_id' => $this->mien->id]);

    $this->actingAs($user)->postJson('/api/v1/cash-sessions/open', [
        'warehouse_id' => $this->autre->id,
        'opening_amount' => 100,
    ])->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
});

it('laisse une vue multi-lieux agir sur n\'importe quel lieu', function (): void {
    // stock.view_global : c'est la permission qui ouvre le multi-lieux, pas
    // un nom de rôle.
    $admin = grantUser(['inventory.create', 'stock.view_global'], ['warehouse_id' => $this->mien->id]);

    $this->actingAs($admin)->postJson('/api/v1/inventories', [
        'warehouse_id' => $this->autre->id,
        'counted_at' => '2026-08-09',
    ])->assertCreated();
});

it('bloque un utilisateur sans lieu rattaché', function (): void {
    $user = grantUser(['inventory.create']);
    $user->update(['warehouse_id' => null]);

    $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $this->mien->id,
        'counted_at' => '2026-08-09',
    ])->assertStatus(422);
});
