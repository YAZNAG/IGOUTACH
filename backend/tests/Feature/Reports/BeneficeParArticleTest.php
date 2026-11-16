<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Bénéfice par article : la liste entière des articles vendus, filtrable par
 * période, par jour et par lieu, cherchable, exportable.
 */
function venteConfirmeeBenefice(Warehouse $lieu, string $quand, array $lignes): Sale
{
    $total = array_sum(array_map(fn (array $l): float => $l['qte'] * $l['pu'], $lignes));
    $vente = Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => Customer::factory()->create(['warehouse_id' => $lieu->id])->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $total,
        'discount_percent' => 0,
        'total' => $total,
        'paid_amount' => $total,
        'payment_status' => 'paid',
        'confirmed_at' => $quand,
    ]);
    foreach ($lignes as $l) {
        SaleLine::query()->create([
            'sale_id' => $vente->id,
            'product_id' => $l['produit']->id,
            'quantity' => $l['qte'],
            'unit_price' => $l['pu'],
            'price_type_code' => 'detail',
            'line_total' => $l['qte'] * $l['pu'],
        ]);
    }

    return $vente;
}

it('liste tous les articles vendus avec leur benefice, par periode, par jour et par lieu', function (): void {
    $tarrast = Warehouse::factory()->create();
    $peugeot = Warehouse::factory()->create();
    $cable = Product::factory()->create(['sku' => 'CAB-1', 'name' => 'CABLE HDMI', 'cost_price' => 20]);
    $tele = Product::factory()->create(['sku' => 'TV-1', 'name' => 'TC RECSON LED', 'cost_price' => 1200]);

    venteConfirmeeBenefice($tarrast, '2026-11-10 10:00', [['produit' => $cable, 'qte' => 5, 'pu' => 30]]);        // +50
    venteConfirmeeBenefice($tarrast, '2026-11-12 10:00', [['produit' => $tele, 'qte' => 1, 'pu' => 1500]]);       // +300
    venteConfirmeeBenefice($peugeot, '2026-11-12 15:00', [['produit' => $cable, 'qte' => 2, 'pu' => 15]]);        // -10 (vendu sous le coût)
    venteConfirmeeBenefice($tarrast, '2026-11-20 10:00', [['produit' => $cable, 'qte' => 1, 'pu' => 30]]);        // hors période

    $user = grantUser(['report.consolidated', 'product.view_cost_price']);

    $periode = test()->actingAs($user)->getJson('/api/v1/reports/profit-by-product?from=2026-11-10&to=2026-11-15')
        ->assertOk()->json('data');
    expect($periode['rows'])->toHaveCount(2)
        ->and($periode['rows'][0]['sku'])->toBe('TV-1')
        ->and((float) $periode['rows'][0]['profit'])->toBe(300.0)
        ->and((float) $periode['rows'][1]['profit'])->toBe(40.0)
        ->and($periode['rows'][1]['quantity'])->toBe(7)
        ->and((float) $periode['totals']['profit'])->toBe(340.0)
        ->and((float) $periode['totals']['revenue'])->toBe(1680.0);

    // Un seul jour : le jour l'emporte sur la période.
    $jour = test()->actingAs($user)->getJson('/api/v1/reports/profit-by-product?from=2026-11-01&to=2026-11-30&day=2026-11-12')
        ->assertOk()->json('data');
    expect($jour['from'])->toBe('2026-11-12')->and($jour['to'])->toBe('2026-11-12')
        ->and($jour['rows'])->toHaveCount(2)
        ->and((float) $jour['totals']['profit'])->toBe(290.0);

    // Un lieu : la vente à perte de peugeot ressort seule, et négative.
    $lieu = test()->actingAs($user)->getJson("/api/v1/reports/profit-by-product?from=2026-11-01&to=2026-11-30&warehouse_id={$peugeot->id}")
        ->assertOk()->json('data');
    expect($lieu['rows'])->toHaveCount(1)->and((float) $lieu['rows'][0]['profit'])->toBe(-10.0);

    // Recherche sur la référence ou le nom.
    $recherche = test()->actingAs($user)->getJson('/api/v1/reports/profit-by-product?from=2026-11-01&to=2026-11-30&q=recson')
        ->assertOk()->json('data');
    expect($recherche['rows'])->toHaveCount(1)->and($recherche['rows'][0]['name'])->toBe('TC RECSON LED');
});

it('exporte la liste en Excel et refuse le benefice sans le droit de voir les couts', function (): void {
    $lieu = Warehouse::factory()->create();
    $produit = Product::factory()->create(['cost_price' => 10]);
    venteConfirmeeBenefice($lieu, '2026-11-12 10:00', [['produit' => $produit, 'qte' => 3, 'pu' => 25]]);

    $direction = grantUser(['report.consolidated', 'product.view_cost_price']);
    $export = test()->actingAs($direction)->get('/api/v1/reports/profit-by-product?day=2026-11-12&format=xlsx');
    $export->assertOk();
    expect($export->headers->get('content-disposition'))->toContain('IGOUTECH_benefice-articles_2026-11-12.xlsx');

    $sansCouts = grantUser(['report.consolidated']);
    test()->actingAs($sansCouts)->getJson('/api/v1/reports/profit-by-product?day=2026-11-12')->assertForbidden();
});
