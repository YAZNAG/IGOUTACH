<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Fiche article : le prix applique a chaque vente, et son evolution.
 */
function venteAvecPrix(
    Product $produit,
    Warehouse $lieu,
    Customer $client,
    int $quantite,
    float $prix,
    string $reference,
): Sale {
    $vente = Sale::query()->create([
        'reference' => $reference,
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $quantite * $prix,
        'discount_percent' => 0,
        'total' => $quantite * $prix,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => now(),
    ]);

    $vente->lines()->create([
        'product_id' => $produit->id,
        'quantity' => $quantite,
        'unit_price' => $prix,
        'line_total' => $quantite * $prix,
    ]);

    return $vente;
}

it('rend le prix applique a chaque vente', function (): void {
    $user = grantUser(['product.view', 'product.view_cost_price']);
    $produit = Product::factory()->create(['cost_price' => 60]);
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create(['name' => 'Comptoir Souss']);

    venteAvecPrix($produit, $lieu, $client, 2, 100, 'VTE-P1');
    venteAvecPrix($produit, $lieu, $client, 8, 80, 'VTE-P2');

    $data = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/statistics")
        ->assertOk()->json('data');

    $ventes = collect($data['recent_sales']);

    expect($ventes)->toHaveCount(2)
        ->and($ventes->firstWhere('reference', 'VTE-P1')['unit_price'])->toEqual(100.0)
        ->and($ventes->firstWhere('reference', 'VTE-P2')['unit_price'])->toEqual(80.0)
        ->and($ventes->firstWhere('reference', 'VTE-P1')['customer'])->toBe('Comptoir Souss');
});

it('pondere le prix moyen par les quantites vendues', function (): void {
    $user = grantUser(['product.view', 'product.view_cost_price']);
    $produit = Product::factory()->create(['cost_price' => 50]);
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create();

    // 2 x 100 et 8 x 80 : la moyenne simple donnerait 90, la ponderee 84.
    venteAvecPrix($produit, $lieu, $client, 2, 100, 'VTE-M1');
    venteAvecPrix($produit, $lieu, $client, 8, 80, 'VTE-M2');

    $data = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/statistics")
        ->assertOk()->json('data');

    $moisCourant = collect($data['price_history'])->last();

    expect($moisCourant['avg'])->toEqual(84.0)
        ->and($moisCourant['min'])->toEqual(80.0)
        ->and($moisCourant['max'])->toEqual(100.0)
        ->and($moisCourant['cost'])->toEqual(50.0);
});

it('laisse a vide les mois sans vente plutot que de les mettre a zero', function (): void {
    $user = grantUser(['product.view', 'product.view_cost_price']);
    $produit = Product::factory()->create(['cost_price' => 10]);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/statistics")
        ->assertOk()->json('data');

    // Un prix a zero n'a jamais ete pratique : la courbe ne doit pas plonger.
    expect(collect($data['price_history'])->pluck('avg')->unique()->all())->toBe([null]);
});

it('cache le prix d\'achat a qui n\'a pas le droit de voir les couts', function (): void {
    $user = grantUser(['product.view']);
    $produit = Product::factory()->create(['cost_price' => 60]);
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create();

    venteAvecPrix($produit, $lieu, $client, 1, 120, 'VTE-C1');

    $data = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/statistics")
        ->assertOk()->json('data');

    // Le cout se deduirait de la courbe de prix aussi bien que de la marge :
    // les deux chemins doivent etre fermes ensemble.
    expect($data)->not->toHaveKey('recent_purchases')
        ->and($data)->not->toHaveKey('gross_margin')
        ->and($data['price_history'][0])->not->toHaveKey('cost')
        // Le prix de vente, lui, reste visible : ce n'est pas un cout.
        ->and($data['recent_sales'][0]['unit_price'])->toEqual(120.0);
});
