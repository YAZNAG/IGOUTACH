<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Le découpage de l'activité, servi aux deux pages : chiffre d'affaires et
 * bénéfice sortent de la même requête.
 */
function venteDecoupage(Warehouse $lieu, ?Customer $client, Product $produit, float $prix): Sale
{
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

it('decoupe l\'activite par lieu, client, article et fournisseur', function (): void {
    $user = grantUser(['report.consolidated', 'product.view_cost_price']);
    $lieu = Warehouse::factory()->create(['code' => 'PV-TEST']);
    $client = Customer::factory()->create(['name' => 'Client Alpha']);
    $produit = Product::factory()->create(['sku' => 'REF-1', 'cost_price' => 40]);

    // Le rattachement au fournisseur vient des receptions, pas du catalogue :
    // « product_supplier » n'est pas rempli en pratique.
    $fournisseur = Supplier::factory()->create(['name' => 'Fournisseur Beta']);
    $reception = App\Domain\Purchasing\Models\GoodsReceipt::create([
        'number' => 'BR-DEC-1',
        'supplier_id' => $fournisseur->id,
        'warehouse_id' => $lieu->id,
        'received_at' => now()->subDay(),
        'payment_status' => 'unpaid',
        'amount_paid' => '0.00',
    ]);
    $reception->lines()->create([
        'product_id' => $produit->id,
        'quantity' => 5,
        'unit_price' => '40.00',
        'position' => 0,
    ]);

    venteDecoupage($lieu, $client, $produit, 100);

    $data = test()->actingAs($user)
        ->getJson('/api/v1/reports/breakdown')
        ->assertOk()->json('data');

    expect($data['totals']['revenue'])->toEqual(100.0)
        ->and($data['totals']['profit'])->toEqual(60.0)
        ->and($data['by_warehouse'][0]['name'])->toBe('PV-TEST')
        ->and($data['by_customer'][0]['name'])->toBe('Client Alpha')
        ->and($data['by_product'][0]['name'])->toContain('REF-1')
        ->and($data['by_product'][0]['quantity'])->toBe(1)
        ->and($data['by_supplier'][0]['name'])->toBe('Fournisseur Beta')
        ->and($data['by_supplier'][0]['profit'])->toEqual(60.0);
});

it('regroupe les ventes sans client sous le client de passage', function (): void {
    $user = grantUser(['report.consolidated', 'product.view_cost_price']);
    $lieu = Warehouse::factory()->create();
    $produit = Product::factory()->create(['cost_price' => 10]);

    venteDecoupage($lieu, null, $produit, 70);

    $data = test()->actingAs($user)
        ->getJson('/api/v1/reports/breakdown')
        ->assertOk()->json('data');

    $passage = collect($data['by_customer'])->firstWhere('name', 'Client de passage');

    // Les ecarter ferait manquer une part du benefice ; les eclater en lignes
    // anonymes n'apprendrait rien.
    expect($passage)->not->toBeNull()
        ->and($passage['revenue'])->toEqual(70.0)
        ->and($passage['profit'])->toEqual(60.0);
});

it('cache le benefice a qui ne peut pas voir les couts', function (): void {
    $user = grantUser(['report.consolidated']);
    $lieu = Warehouse::factory()->create();
    venteDecoupage($lieu, Customer::factory()->create(), Product::factory()->create(['cost_price' => 10]), 70);

    $data = test()->actingAs($user)
        ->getJson('/api/v1/reports/breakdown')
        ->assertOk()->json('data');

    expect($data['totals']['revenue'])->toEqual(70.0)
        ->and($data['totals'])->not->toHaveKey('profit');

    foreach (['by_warehouse', 'by_customer', 'by_product', 'by_supplier'] as $bloc) {
        foreach ($data[$bloc] as $ligne) {
            expect($ligne)->not->toHaveKey('profit')
                ->and($ligne)->not->toHaveKey('cost');
        }
    }
});
