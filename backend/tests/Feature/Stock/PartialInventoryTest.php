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

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'adjustment'], ['name' => 'Ajustement', 'sign' => 0, 'affects_valuation' => false]);
});

function partialProduct(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function partialStock(int $warehouseId, int $productId, int $qty): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);
}

it('compte quelques articles, reprend un autre jour, et ne touche pas aux non comptés', function (): void {
    $user = grantUser(['inventory.create', 'inventory.approve']);
    $warehouse = Warehouse::factory()->create();

    $a = partialProduct();
    $b = partialProduct();
    $c = partialProduct(); // jamais compté
    partialStock($warehouse->id, $a->id, 10);
    partialStock($warehouse->id, $b->id, 20);
    partialStock($warehouse->id, $c->id, 30);

    $id = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-08-01',
    ])->assertCreated()->json('data.id');

    // Jour 1 : on ne compte QUE l'article A.
    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $a->id, 'counted_quantity' => 8, 'reason' => 'Casse']],
    ])->assertOk()->assertJsonCount(1, 'data.lines');

    // Jour 2 : on reprend le même inventaire à une autre date, on compte B.
    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}", [
        'counted_at' => '2026-08-03',
    ])->assertOk()->assertJsonPath('data.counted_at', '2026-08-03');

    $res = $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $b->id, 'counted_quantity' => 25, 'reason' => 'Retrouvé']],
    ])->assertOk();

    // Le comptage du jour 1 est conservé : 2 lignes au total.
    expect($res->json('data.lines'))->toHaveCount(2);

    // Validation : seuls A et B sont régularisés, C garde son stock.
    $this->actingAs($user)->postJson("/api/v1/inventories/{$id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    $reader = app(StockReaderInterface::class);
    expect($reader->quantityFor($warehouse->id, $a->id))->toBe(8)
        ->and($reader->quantityFor($warehouse->id, $b->id))->toBe(25)
        ->and($reader->quantityFor($warehouse->id, $c->id))->toBe(30);
});

it('corrige un comptage déjà saisi sans dupliquer la ligne', function (): void {
    $user = grantUser(['inventory.create']);
    $warehouse = Warehouse::factory()->create();
    $product = partialProduct();
    partialStock($warehouse->id, $product->id, 50);

    $id = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-08-01',
    ])->json('data.id');

    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 45, 'reason' => 'Erreur']],
    ])->assertOk();

    $res = $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 48, 'reason' => 'Recomptage']],
    ])->assertOk();

    expect($res->json('data.lines'))->toHaveCount(1)
        ->and($res->json('data.lines.0.counted_quantity'))->toBe(48)
        ->and($res->json('data.lines.0.difference'))->toBe(-2);
});

it('retire un comptage saisi par erreur', function (): void {
    $user = grantUser(['inventory.create']);
    $warehouse = Warehouse::factory()->create();
    $product = partialProduct();
    partialStock($warehouse->id, $product->id, 15);

    $id = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-08-01',
    ])->json('data.id');

    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 12, 'reason' => 'Casse']],
    ])->assertOk();

    $this->actingAs($user)->deleteJson("/api/v1/inventories/{$id}/lines/{$product->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data.lines');
});

it('refuse de modifier un inventaire déjà validé', function (): void {
    $user = grantUser(['inventory.create', 'inventory.approve']);
    $warehouse = Warehouse::factory()->create();

    $inventory = Inventory::query()->create([
        'reference' => 'INV-PARTIAL',
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-08-01',
        'status' => Inventory::STATUS_APPROVED,
    ]);

    $this->actingAs($user)->putJson("/api/v1/inventories/{$inventory->id}", [
        'counted_at' => '2026-08-05',
    ])->assertStatus(422);
});
