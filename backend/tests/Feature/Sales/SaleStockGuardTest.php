<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);

    $this->lieu = Warehouse::factory()->create();
    $this->user = grantUser(['sale.create', 'stock.view'], ['warehouse_id' => $this->lieu->id]);
});

function venteProduit(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'sale_price' => 100,
    ]);
}

function venteStock(int $warehouseId, int $productId, int $qty): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouseId, 'product_id' => $productId,
        'quantity' => $qty, 'reserved_quantity' => 0, 'average_cost' => '50.00',
    ]);
}

it('refuse une facture dont la quantité dépasse le stock du lieu', function (): void {
    $product = venteProduit();
    venteStock($this->lieu->id, $product->id, 10);

    $reponse = $this->actingAs($this->user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $this->lieu->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 12, 'unit_price' => 100]],
    ])->assertStatus(422);

    // Le message doit dire ce qui manque, pas seulement « impossible ».
    expect($reponse->json('insufficient.0.requested'))->toBe(12)
        ->and($reponse->json('insufficient.0.available'))->toBe(10)
        ->and(Sale::withoutGlobalScopes()->count())->toBe(0);
});

it('accepte une facture à hauteur exacte du stock', function (): void {
    $product = venteProduit();
    venteStock($this->lieu->id, $product->id, 10);

    $this->actingAs($this->user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $this->lieu->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100]],
    ])->assertCreated();
});

it('additionne les lignes portant sur le même article', function (): void {
    $product = venteProduit();
    venteStock($this->lieu->id, $product->id, 10);

    // Chaque ligne passe isolément (6 ≤ 10) mais le total demandé est 12.
    $this->actingAs($this->user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $this->lieu->id,
        'lines' => [
            ['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 100],
            ['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 100],
        ],
    ])->assertStatus(422);

    expect(Sale::withoutGlobalScopes()->count())->toBe(0);
});

it('refuse un article sans aucun stock dans ce lieu', function (): void {
    $product = venteProduit();
    // Aucune ligne de stock : l'article n'a jamais été approvisionné ici.

    $this->actingAs($this->user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $this->lieu->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
    ])->assertStatus(422);
});

it('laisse un devis porter sur ce qui n\'est pas en stock', function (): void {
    $user = grantUser(['sale.create', 'quote.create'], ['warehouse_id' => $this->lieu->id]);
    $product = venteProduit();
    venteStock($this->lieu->id, $product->id, 2);

    // Un devis n'engage aucune sortie : il peut porter sur ce qu'on
    // commandera ensuite.
    $this->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'quote',
        'warehouse_id' => $this->lieu->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 100, 'unit_price' => 100]],
    ])->assertCreated();
});

it('ne juge que le stock du lieu de la vente', function (): void {
    $autre = Warehouse::factory()->create();
    $product = venteProduit();

    // Marchandise abondante ailleurs, absente ici : la vente doit être refusée.
    venteStock($autre->id, $product->id, 500);
    venteStock($this->lieu->id, $product->id, 1);

    $this->actingAs($this->user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $this->lieu->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]],
    ])->assertStatus(422);
});
