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
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'adjustment'], ['name' => 'Ajustement', 'sign' => 0, 'affects_valuation' => false]);
});

function applyProduit(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function applyStock(int $warehouseId, int $productId, int $qty): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);
}

it('applique le comptage saisi, y compris zéro, et laisse le reste intact', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['inventory.create', 'inventory.approve', 'stock.view'], ['warehouse_id' => $warehouse->id]);

    $hausse = applyProduit();   // compté au-dessus du théorique
    $baisse = applyProduit();   // compté en dessous
    $azero  = applyProduit();   // compté à ZÉRO — doit tomber à 0
    $intact = applyProduit();   // jamais saisi — doit rester inchangé

    applyStock($warehouse->id, $hausse->id, 10);
    applyStock($warehouse->id, $baisse->id, 50);
    applyStock($warehouse->id, $azero->id, 121);
    applyStock($warehouse->id, $intact->id, 77);

    $id = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => '2026-08-09',
    ])->assertCreated()->json('data.id');

    // Seuls trois articles sont comptés ; le quatrième n'est pas transmis,
    // ce qui traduit un champ laissé vide à l'écran.
    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [
            ['product_id' => $hausse->id, 'counted_quantity' => 14],
            ['product_id' => $baisse->id, 'counted_quantity' => 45],
            ['product_id' => $azero->id, 'counted_quantity' => 0],
        ],
    ])->assertOk()->assertJsonCount(3, 'data.lines');

    $this->actingAs($user)->postJson("/api/v1/inventories/{$id}/approve")
        ->assertOk()->assertJsonPath('data.status', 'approved');

    $lecteur = app(StockReaderInterface::class);

    expect($lecteur->quantityFor($warehouse->id, $hausse->id))->toBe(14)
        ->and($lecteur->quantityFor($warehouse->id, $baisse->id))->toBe(45)
        // Zéro saisi est une valeur, pas une absence de saisie.
        ->and($lecteur->quantityFor($warehouse->id, $azero->id))->toBe(0)
        // Champ laissé vide : l'article garde son stock.
        ->and($lecteur->quantityFor($warehouse->id, $intact->id))->toBe(77);
});

it('ne génère aucun mouvement quand le comptage confirme le théorique', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['inventory.create', 'inventory.approve'], ['warehouse_id' => $warehouse->id]);
    $product = applyProduit();
    applyStock($warehouse->id, $product->id, 30);

    $id = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id, 'counted_at' => '2026-08-09',
    ])->json('data.id');

    $this->actingAs($user)->putJson("/api/v1/inventories/{$id}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 30]],
    ])->assertOk();

    $this->actingAs($user)->postJson("/api/v1/inventories/{$id}/approve")->assertOk();

    // Un écart nul ne doit pas polluer l'historique d'un mouvement à zéro.
    expect(\App\Domain\Stock\Models\StockMovement::withoutGlobalScopes()
        ->where('reference_type', 'inventory')->count())->toBe(0)
        ->and(app(StockReaderInterface::class)->quantityFor($warehouse->id, $product->id))->toBe(30);
});

it('expose la quantité théorique de chaque article du lieu', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.view', 'inventory.create'], ['warehouse_id' => $warehouse->id]);
    $a = applyProduit();
    $b = applyProduit();
    applyStock($warehouse->id, $a->id, 42);
    // b n'a aucune ligne de stock : il doit apparaître à 0, pas disparaître.

    $lignes = collect($this->actingAs($user)
        ->getJson('/api/v1/stock?warehouse_id='.$warehouse->id.'&per_page=100')
        ->assertOk()->json('data'));

    expect($lignes->firstWhere('product_id', $a->id)['quantity'])->toBe(42)
        ->and($lignes->firstWhere('product_id', $b->id)['quantity'])->toBe(0);
});
