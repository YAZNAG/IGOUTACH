<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Activité d'un lieu ou d'un client : chiffre d'affaires, coût, bénéfice.
 *
 * Le bénéfice se déduit du chiffre d'affaires moins le coût. Le laisser
 * passer à qui n'a pas « product.view_cost_price » contournerait cette
 * permission aussi sûrement que d'afficher le prix d'achat.
 */
function venteActivite(Warehouse $lieu, ?Customer $client, float $prix, float $cout): Sale
{
    $produit = Product::factory()->create(['cost_price' => $cout]);

    $vente = Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client?->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $prix,
        'discount_percent' => 0,
        'total' => $prix,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => now(),
    ]);

    $vente->lines()->create([
        'product_id' => $produit->id,
        'quantity' => 1,
        'unit_price' => $prix,
        'line_total' => $prix,
    ]);

    return $vente;
}

it('donne le benefice d\'un lieu a qui peut voir les couts', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['warehouse.view', 'product.view_cost_price', 'stock.view_global']);

    venteActivite($lieu, Customer::factory()->create(), 250, 100);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/warehouses/{$lieu->id}/stats")
        ->assertOk()->json('data');

    expect($data['totals']['total'])->toEqual(250.0)
        ->and($data['totals']['cost'])->toEqual(100.0)
        ->and($data['totals']['profit'])->toEqual(150.0)
        ->and($data['totals']['margin_percent'])->toEqual(60.0);

    $moisEnCours = collect($data['monthly'])->last();
    expect($moisEnCours)->toHaveKey('profit')
        ->and($moisEnCours['profit'])->toEqual(150.0);
});

it('cache le cout et le benefice a qui n\'y a pas droit', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['warehouse.view', 'stock.view_global']);

    venteActivite($lieu, Customer::factory()->create(), 250, 100);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/warehouses/{$lieu->id}/stats")
        ->assertOk()->json('data');

    // Le chiffre d'affaires reste visible : c'est le cout qui est protege.
    expect($data['totals']['total'])->toEqual(250.0)
        ->and($data['totals'])->not->toHaveKey('cost')
        ->and($data['totals'])->not->toHaveKey('profit')
        ->and($data['totals'])->not->toHaveKey('margin_percent');

    foreach ($data['monthly'] as $point) {
        expect($point)->not->toHaveKey('cost')
            ->and($point)->not->toHaveKey('profit');
    }
});

it('ne multiplie pas le chiffre d\'affaires d\'une vente a plusieurs lignes', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['warehouse.view', 'product.view_cost_price', 'stock.view_global']);

    $vente = venteActivite($lieu, Customer::factory()->create(), 200, 80);
    // Une seconde ligne sur la meme vente : la jointure sur les lignes
    // compterait le total deux fois si le cout n'etait pas agrege a part.
    $vente->lines()->create([
        'product_id' => Product::factory()->create(['cost_price' => 20])->id,
        'quantity' => 1,
        'unit_price' => 0,
        'line_total' => 0,
    ]);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/warehouses/{$lieu->id}/stats")
        ->assertOk()->json('data');

    expect($data['totals']['total'])->toEqual(200.0)
        ->and($data['totals']['cost'])->toEqual(100.0)
        ->and($data['totals']['documents'])->toBe(1);
});

it('donne le benefice d\'un client', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create();
    $user = grantUser(['customer.view', 'product.view_cost_price', 'customer.view_all']);

    venteActivite($lieu, $client, 400, 150);

    $data = test()->actingAs($user)
        ->getJson("/api/v1/customers/{$client->id}/stats")
        ->assertOk()->json('data');

    expect($data['totals']['profit'])->toEqual(250.0)
        ->and($data['totals']['margin_percent'])->toEqual(62.5);
});
