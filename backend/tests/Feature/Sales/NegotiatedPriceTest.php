<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Prix négocié sur une vente : le vendeur fixe le prix de cette vente-là, sans
 * toucher au tarif de l'article, et jamais sous le coût d'achat.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

function negoProduit(float $cout = 100.0): Product
{
    return Product::factory()->create([
        'name' => 'ARTICLE NEGOCIE',
        'cost_price' => $cout,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

/** @return array{0: \App\Models\User, 1: Warehouse, 2: Product} */
function negoContexte(array $permissions = ['sale.create'], float $cout = 100.0): array
{
    $lieu = Warehouse::factory()->create();
    $user = grantUser($permissions, ['warehouse_id' => $lieu->id]);
    $produit = negoProduit($cout);

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $lieu->id,
        'product_id' => $produit->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
        'average_cost' => (string) $cout,
    ]);

    return [$user, $lieu, $produit];
}

it('applique le prix saisi a la vente sans toucher au tarif de l\'article', function (): void {
    [$user, $lieu, $produit] = negoContexte();

    $id = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 2, 'unit_price' => 150]],
    ])->assertCreated()->json('data.id');

    $vente = Sale::query()->find($id);
    expect((float) $vente->lines()->first()->unit_price)->toBe(150.0)
        ->and((float) $vente->total)->toBe(300.0);

    // Le catalogue est inchangé : le prix négocié ne vaut que pour cette vente.
    expect((float) $produit->fresh()->sale_price)->toBe((float) $produit->sale_price);
});

it('refuse un prix inferieur au cout de l\'article', function (): void {
    [$user, $lieu, $produit] = negoContexte(cout: 100.0);

    test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 99.99]],
    ])->assertStatus(422);

    expect(Sale::count())->toBe(0);
});

it('accepte un prix egal au cout, qui est le plancher', function (): void {
    [$user, $lieu, $produit] = negoContexte(cout: 100.0);

    test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 100]],
    ])->assertCreated();
});

it('ne revele pas le cout dans le refus a qui n\'a pas le droit de le voir', function (): void {
    [$user, $lieu, $produit] = negoContexte(cout: 137.50);

    $message = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 10]],
    ])->assertStatus(422)->json('message');

    expect($message)->not->toContain('137.50')
        ->and($message)->not->toContain('137,50');
});

/**
 * Le résolveur exige un tarif : sans lui, `/sales/price` répond 422 avant même
 * d'arriver au plancher, et le cas testé ne serait pas celui qu'on croit.
 */
function negoTarif(Product $produit, float $montant = 200.0): void
{
    // La base de test ne porte pas les niveaux de prix : on crée celui dont on
    // a besoin plutôt que de supposer un jeu de données amorcé.
    $detail = PriceType::query()->firstOrCreate(
        ['code' => PriceType::DETAIL],
        ['name' => 'Détail', 'rank' => 1, 'min_quantity' => 1, 'is_active' => true],
    );

    ProductPrice::query()->create([
        'product_id' => $produit->id,
        'price_type_id' => $detail->id,
        'amount' => $montant,
        'valid_from' => now()->subDay(),
    ]);
}

it('ne renvoie pas le plancher a qui ne voit pas les prix d\'achat', function (): void {
    [$user, , $produit] = negoContexte(cout: 137.50);
    negoTarif($produit);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/sales/price?product_id={$produit->id}&quantity=1")
        ->assertOk()->json('data');

    // Le plancher EST le prix d'achat : le publier contournerait la permission.
    expect($data['floor_price'])->toBeNull()
        ->and($data['floor_visible'])->toBeFalse();
});

it('renvoie le plancher a qui voit les prix d\'achat', function (): void {
    [$user, , $produit] = negoContexte(['sale.create', 'product.view_cost_price'], 137.50);
    negoTarif($produit);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/sales/price?product_id={$produit->id}&quantity=1")
        ->assertOk()->json('data');

    expect((float) $data['floor_price'])->toBe(137.50)
        ->and($data['floor_visible'])->toBeTrue();
});

it('applique la meme regle a la modification d\'un brouillon', function (): void {
    [$user, $lieu, $produit] = negoContexte(cout: 100.0);

    $id = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_QUOTE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 200]],
    ])->assertCreated()->json('data.id');

    // Sans ce contrôle, on contournerait le plancher en créant une ligne
    // conforme puis en la ramenant sous le coût.
    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 20]],
    ])->assertStatus(422);

    expect((float) Sale::query()->find($id)->lines()->first()->unit_price)->toBe(200.0);
});
