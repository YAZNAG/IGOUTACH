<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Modification d'un document tant qu'il est en brouillon : ajout et retrait
 * d'articles, changement de quantités. Fermée dès la confirmation.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

function editProduit(string $nom): Product
{
    return Product::factory()->create([
        'name' => $nom,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function editStock(int $lieu, int $produit, int $qte): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $lieu,
        'product_id' => $produit,
        'quantity' => $qte,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);
}

/** @return array{0: \App\Models\User, 1: int, 2: array<int, Product>} */
function editDevis(string $type = Sale::TYPE_QUOTE): array
{
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['sale.create', 'sale.sell_below_floor'], ['warehouse_id' => $lieu->id]);

    $a = editProduit('ARTICLE A');
    $b = editProduit('ARTICLE B');
    editStock($lieu->id, $a->id, 100);
    editStock($lieu->id, $b->id, 100);

    $id = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => $type,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $a->id, 'quantity' => 2, 'unit_price' => 100]],
    ])->assertCreated()->json('data.id');

    return [$user, (int) $id, ['a' => $a, 'b' => $b, 'lieu' => $lieu]];
}

it('ajoute un article a un devis en brouillon', function (): void {
    [$user, $id, $ctx] = editDevis();

    $reponse = test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [
            ['product_id' => $ctx['a']->id, 'quantity' => 2, 'unit_price' => 100],
            ['product_id' => $ctx['b']->id, 'quantity' => 3, 'unit_price' => 50],
        ],
    ])->assertOk();

    expect($reponse->json('data.lines'))->toHaveCount(2)
        ->and((float) $reponse->json('data.total'))->toBe(350.0);
});

it('retire un article et recalcule le total', function (): void {
    [$user, $id, $ctx] = editDevis();

    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [
            ['product_id' => $ctx['a']->id, 'quantity' => 2, 'unit_price' => 100],
            ['product_id' => $ctx['b']->id, 'quantity' => 3, 'unit_price' => 50],
        ],
    ])->assertOk();

    $reponse = test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $ctx['b']->id, 'quantity' => 3, 'unit_price' => 50]],
    ])->assertOk();

    expect($reponse->json('data.lines'))->toHaveCount(1)
        ->and((float) $reponse->json('data.total'))->toBe(150.0);

    // La ligne retirée ne doit pas survivre en base.
    expect(Sale::query()->find($id)->lines()->count())->toBe(1);
});

it('change la quantite d\'une ligne', function (): void {
    [$user, $id, $ctx] = editDevis();

    $reponse = test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $ctx['a']->id, 'quantity' => 7, 'unit_price' => 100]],
    ])->assertOk();

    expect($reponse->json('data.lines.0.quantity'))->toBe(7)
        ->and((float) $reponse->json('data.total'))->toBe(700.0);
});

it('refuse de modifier un document confirme', function (): void {
    [$user, $id, $ctx] = editDevis(Sale::TYPE_INVOICE);

    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/confirm")->assertOk();

    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $ctx['a']->id, 'quantity' => 9, 'unit_price' => 100]],
    ])->assertStatus(422);

    // Le document confirmé reste tel quel : sa quantité d'origine.
    expect((int) Sale::query()->find($id)->lines()->first()->quantity)->toBe(2);
});

it('refuse de modifier un document annule', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['sale.create', 'sale.cancel'], ['warehouse_id' => $lieu->id]);
    $produit = editProduit('ARTICLE C');
    editStock($lieu->id, $produit->id, 50);

    $id = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_QUOTE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 80]],
    ])->assertCreated()->json('data.id');

    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/cancel")->assertOk();

    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $produit->id, 'quantity' => 4, 'unit_price' => 80]],
    ])->assertStatus(422);
});

it('refuse une facture brouillon dont les quantites depassent le stock', function (): void {
    [$user, $id, $ctx] = editDevis(Sale::TYPE_INVOICE);

    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $ctx['a']->id, 'quantity' => 5000, 'unit_price' => 100]],
    ])->assertStatus(422)
        ->assertJsonPath('errors.lines.0', fn (string $m): bool => str_contains($m, 'ARTICLE A'));
});

it('laisse un devis porter sur une quantite absente du stock', function (): void {
    // Un devis peut porter sur ce qu'on va commander : il ne sort rien.
    [$user, $id, $ctx] = editDevis();

    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $ctx['a']->id, 'quantity' => 5000, 'unit_price' => 100]],
    ])->assertOk();
});

it('applique le plafond de remise a la modification comme a la creation', function (): void {
    [$user, $id, $ctx] = editDevis();

    test()->actingAs($user)->putJson("/api/v1/sales/{$id}", [
        'discount_percent' => 40,
        'lines' => [['product_id' => $ctx['a']->id, 'quantity' => 2, 'unit_price' => 100]],
    ])->assertStatus(422);
});

it('interdit la modification a un autre vendeur', function (): void {
    [, $id, $ctx] = editDevis();

    $autre = grantUser(['sale.create'], ['warehouse_id' => $ctx['lieu']->id]);

    test()->actingAs($autre)->putJson("/api/v1/sales/{$id}", [
        'lines' => [['product_id' => $ctx['a']->id, 'quantity' => 3, 'unit_price' => 100]],
    ])->assertForbidden();
});
