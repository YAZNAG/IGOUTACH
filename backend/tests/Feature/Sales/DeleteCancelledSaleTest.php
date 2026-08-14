<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Customers\Models\Customer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Suppression d'une vente annulée : ouverte seulement à celles qui n'ont
 * laissé aucune trace ailleurs.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    // L'annulation d'une facture confirmee rend le stock via « return_in ».
    MovementType::firstOrCreate(['code' => 'return_in'], ['name' => 'Retour entrant', 'sign' => 1, 'affects_valuation' => false]);
});

/** @return array{0: \App\Models\User, 1: Warehouse, 2: Product} */
function suppContexte(array $permissions = ['sale.create', 'sale.cancel']): array
{
    $lieu = Warehouse::factory()->create();
    $user = grantUser($permissions, ['warehouse_id' => $lieu->id]);
    $produit = Product::factory()->create([
        'cost_price' => 10,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $lieu->id,
        'product_id' => $produit->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);

    return [$user, $lieu, $produit];
}

function suppVente(\App\Models\User $user, Warehouse $lieu, Product $produit): int
{
    return (int) test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 2, 'unit_price' => 100]],
    ])->assertCreated()->json('data.id');
}

it('supprime une vente annulee avant confirmation', function (): void {
    [$user, $lieu, $produit] = suppContexte();
    $id = suppVente($user, $lieu, $produit);

    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/cancel")->assertOk();

    test()->actingAs($user)->deleteJson("/api/v1/sales/{$id}")->assertNoContent();

    expect(Sale::query()->find($id))->toBeNull()
        // Les lignes partent avec la vente : les laisser ferait des orphelines.
        ->and(DB::table('sale_lines')->where('sale_id', $id)->count())->toBe(0);
});

it('refuse de supprimer une vente qui n\'est pas annulee', function (): void {
    [$user, $lieu, $produit] = suppContexte();
    $id = suppVente($user, $lieu, $produit);

    test()->actingAs($user)->deleteJson("/api/v1/sales/{$id}")->assertStatus(422);

    expect(Sale::query()->find($id))->not->toBeNull();
});

it('refuse de supprimer une vente annulee qui a mouvemente le stock', function (): void {
    [$user, $lieu, $produit] = suppContexte();

    // L'annulation d'une facture confirmee ecrit au grand livre du client :
    // une vente au comptoir, sans client, ne suit pas ce chemin.
    $id = (int) test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'customer_id' => Customer::factory()->create()->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 2, 'unit_price' => 100]],
    ])->assertCreated()->json('data.id');

    // Confirmer sort le stock, annuler le rend : les deux mouvements restent
    // au journal et pointeraient dans le vide si la vente disparaissait.
    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/confirm")->assertOk();
    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/cancel")->assertOk();

    $message = test()->actingAs($user)
        ->deleteJson("/api/v1/sales/{$id}")
        ->assertStatus(422)
        ->json('message');

    expect($message)->toContain('stock')
        ->and(Sale::query()->find($id))->not->toBeNull();
});

it('refuse la suppression a qui ne peut pas annuler', function (): void {
    [$user, $lieu, $produit] = suppContexte();
    $id = suppVente($user, $lieu, $produit);
    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/cancel")->assertOk();

    $simple = grantUser(['sale.create'], ['warehouse_id' => $lieu->id]);

    test()->actingAs($simple)->deleteJson("/api/v1/sales/{$id}")->assertForbidden();
});

it('refuse de supprimer un devis dont une facture est issue', function (): void {
    [$user, $lieu, $produit] = suppContexte();

    $devis = (int) test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_QUOTE,
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 100]],
    ])->assertCreated()->json('data.id');

    test()->actingAs($user)->postJson("/api/v1/sales/{$devis}/convert")->assertCreated();
    test()->actingAs($user)->postJson("/api/v1/sales/{$devis}/cancel")->assertOk();

    $message = test()->actingAs($user)
        ->deleteJson("/api/v1/sales/{$devis}")
        ->assertStatus(422)
        ->json('message');

    expect($message)->toContain('facture');
});
