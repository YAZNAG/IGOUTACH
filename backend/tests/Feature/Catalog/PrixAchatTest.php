<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Prix d'achat : consultation, historique, et refus de vendre à perte.
 */
function articleEnStock(float $achat, float $vente, int $quantite, float $coutMoyen): Product
{
    $produit = Product::factory()->create([
        'cost_price' => $achat,
        'sale_price' => $vente,
    ]);

    if ($quantite > 0) {
        Stock::query()->create([
            'product_id' => $produit->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'quantity' => $quantite,
            'average_cost' => $coutMoyen,
        ]);
    }

    return $produit;
}

it('rend le prix d\'achat, le cout utilise et l\'historique', function (): void {
    $user = grantUser(['product.view', 'product.view_cost_price']);
    $produit = articleEnStock(achat: 10, vente: 30, quantite: 20, coutMoyen: 12);

    $lieu = Warehouse::factory()->create(['code' => 'PV-A']);
    $reception = GoodsReceipt::create([
        'number' => 'BR-HIST-1',
        'supplier_id' => Supplier::factory()->create(['name' => 'Fournisseur Un'])->id,
        'warehouse_id' => $lieu->id,
        'received_at' => now()->subDays(3),
        'payment_status' => 'unpaid',
        'amount_paid' => '0.00',
    ]);
    $reception->lines()->create([
        'product_id' => $produit->id,
        'quantity' => 20,
        'unit_price' => '12.00',
        'position' => 0,
    ]);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/purchase-history")
        ->assertOk()->json('data');

    expect($data['purchase_price'])->toEqual(10.0)
        // Le cout utilise est le CMUP, pas le prix de la fiche.
        ->and($data['cost']['cost'])->toEqual(12.0)
        ->and($data['cost']['source'])->toBe('cmup')
        ->and($data['history'])->toHaveCount(1)
        ->and($data['history'][0]['supplier'])->toBe('Fournisseur Un')
        ->and($data['history'][0]['unit_price'])->toEqual(12.0);
});

it('retombe sur le prix d\'achat quand il n\'y a pas de stock', function (): void {
    $user = grantUser(['product.view', 'product.view_cost_price']);
    $produit = articleEnStock(achat: 7, vente: 20, quantite: 0, coutMoyen: 0);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/purchase-history")
        ->assertOk()->json('data');

    // Sans stock il n'y a aucune moyenne a faire : le prix de la fiche fait foi.
    expect($data['cost']['cost'])->toEqual(7.0)
        ->and($data['cost']['source'])->toBe('purchase_price')
        ->and($data['cost']['quantity'])->toBe(0);
});

it('modifie le prix d\'achat', function (): void {
    $user = grantUser(['product.set_price', 'product.view_cost_price']);
    $produit = articleEnStock(achat: 10, vente: 30, quantite: 5, coutMoyen: 12);

    $data = test()->actingAs($user)
        ->patchJson("/api/v1/products/{$produit->id}/purchase-price", ['purchase_price' => 14.5])
        ->assertOk()->json('data');

    expect($data['previous_purchase_price'])->toEqual(10.0)
        ->and($data['purchase_price'])->toEqual(14.5)
        ->and($produit->refresh()->cost_price)->toEqual('14.50');
});

it('refuse un prix d\'achat qui ferait vendre a perte', function (): void {
    $user = grantUser(['product.set_price', 'product.view_cost_price']);
    // Sans stock, le cout de reference est le prix d'achat que l'on pose.
    $produit = articleEnStock(achat: 10, vente: 20, quantite: 0, coutMoyen: 0);

    test()->actingAs($user)
        ->patchJson("/api/v1/products/{$produit->id}/purchase-price", ['purchase_price' => 25])
        ->assertStatus(422)
        ->assertJsonPath('cost', 25);

    // Rien n'est ecrit tant que le prix de vente n'a pas ete revu.
    expect($produit->refresh()->cost_price)->toEqual('10.00');
});

it('refuse un prix de vente inferieur au cout', function (): void {
    $user = grantUser(['product.set_price', 'product.view_cost_price']);
    $produit = articleEnStock(achat: 10, vente: 30, quantite: 20, coutMoyen: 12);

    // 8 DH est sous le CMUP de 12 DH : la vente se ferait a perte.
    test()->actingAs($user)
        ->putJson("/api/v1/products/{$produit->id}/pricing", ['sale_price' => 8])
        ->assertStatus(422)
        ->assertJsonPath('errors.sale_price.0', 'Prix de vente inférieur au coût.');

    expect($produit->refresh()->sale_price)->toEqual('30.00');
});

it('accepte un prix de vente egal au cout', function (): void {
    $user = grantUser(['product.set_price', 'product.view_cost_price']);
    $produit = articleEnStock(achat: 10, vente: 30, quantite: 20, coutMoyen: 12);

    // A perte signifie « en dessous », pas « a l'equilibre ».
    test()->actingAs($user)
        ->putJson("/api/v1/products/{$produit->id}/pricing", ['sale_price' => 12])
        ->assertOk();

    expect($produit->refresh()->sale_price)->toEqual('12.00');
});

it('protege le cout derriere sa permission', function (): void {
    $user = grantUser(['product.view']);
    $produit = articleEnStock(achat: 10, vente: 30, quantite: 5, coutMoyen: 12);

    test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/purchase-history")
        ->assertForbidden();
});
