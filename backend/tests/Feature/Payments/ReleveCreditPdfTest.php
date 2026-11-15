<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use App\Domain\Sales\Services\ReleveCreditClient;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Relevé de crédit : ce qu'on imprime pour réclamer son dû à un client,
 * avec les articles emportés sur chaque facture impayée.
 */
function factureImpayeeAvecArticles(Customer $client, Warehouse $lieu, array $articles, float $paye = 0, string $quand = '2026-10-01 10:00'): Sale
{
    $total = array_sum(array_map(fn (array $a): float => $a['qte'] * $a['pu'], $articles));

    $vente = Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $total,
        'discount_percent' => 0,
        'total' => $total,
        'paid_amount' => $paye,
        'payment_status' => $paye > 0 ? 'partial' : 'unpaid',
        'confirmed_at' => $quand,
    ]);

    foreach ($articles as $a) {
        SaleLine::query()->create([
            'sale_id' => $vente->id,
            'product_id' => $a['produit']->id,
            'quantity' => $a['qte'],
            'unit_price' => $a['pu'],
            'price_type_code' => 'detail',
            'line_total' => $a['qte'] * $a['pu'],
        ]);
    }

    return $vente;
}

it('rassemble les factures dues avec leurs articles et le reste a payer', function (): void {
    $lieu = Warehouse::factory()->create(['code' => 'DCH']);
    $client = Customer::factory()->create(['warehouse_id' => $lieu->id, 'balance' => 0]);
    $cable = Product::factory()->create(['sku' => 'CAB-01', 'name' => 'CABLE HDMI 2M']);
    $tele = Product::factory()->create(['sku' => 'TV-01', 'name' => 'TC RECSON LED 32']);

    factureImpayeeAvecArticles($client, $lieu, [
        ['produit' => $cable, 'qte' => 3, 'pu' => 50],
        ['produit' => $tele, 'qte' => 1, 'pu' => 1500],
    ], paye: 650);
    factureImpayeeAvecArticles($client, $lieu, [['produit' => $cable, 'qte' => 2, 'pu' => 50]]);
    // Une facture soldée ne figure pas sur le relevé : rien à réclamer dessus.
    factureImpayeeAvecArticles($client, $lieu, [['produit' => $cable, 'qte' => 1, 'pu' => 50]], paye: 50);

    $releve = app(ReleveCreditClient::class)->pour($client);

    expect($releve['factures'])->toHaveCount(2)
        ->and($releve['factures'][0]['lignes'])->toHaveCount(2)
        ->and($releve['factures'][0]['lignes'][1]['nom'])->toBe('TC RECSON LED 32')
        ->and($releve['factures'][0]['reste'])->toBe(1000.0)
        ->and($releve['totaux']['facture'])->toBe(1750.0)
        ->and($releve['totaux']['paye'])->toBe(650.0)
        ->and($releve['totaux']['du'])->toBe(1100.0)
        ->and($releve['totaux']['unites'])->toBe(6)
        ->and($releve['par_lieu'][0])->toMatchArray(['code' => 'DCH', 'du' => 1100.0, 'factures' => 2]);
});

it('sert le releve en PDF a qui voit les credits', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create(['warehouse_id' => $lieu->id, 'code' => 'CLI-7', 'balance' => 0]);
    $produit = Product::factory()->create();
    factureImpayeeAvecArticles($client, $lieu, [['produit' => $produit, 'qte' => 2, 'pu' => 120]]);

    $user = grantUser(['credit.view', 'customer.view_all']);

    $reponse = test()->actingAs($user)->get("/api/v1/customers/{$client->id}/credit-pdf");

    $reponse->assertOk();
    expect($reponse->headers->get('content-type'))->toContain('application/pdf')
        ->and($reponse->headers->get('content-disposition'))->toContain('credit-CLI-7.pdf')
        ->and(strlen((string) $reponse->getContent()))->toBeGreaterThan(1000);
});

it('refuse le releve d un client d un autre lieu a un responsable', function (): void {
    $sien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $client = Customer::factory()->create(['warehouse_id' => $autre->id]);
    $responsable = grantUser(['credit.view'], ['warehouse_id' => $sien->id]);

    test()->actingAs($responsable)->get("/api/v1/customers/{$client->id}/credit-pdf")->assertForbidden();
});
