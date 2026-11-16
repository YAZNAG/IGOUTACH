<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleReturn;
use App\Domain\Sales\Services\ProfitReportService;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;

/**
 * Retour client rattaché à la vente : on retrouve la vente, on retourne au
 * plus ce qu'elle contenait, au prix où elle a été faite, et le bénéfice en
 * tient compte.
 */
beforeEach(function (): void {
    $this->seed(MovementTypeSeeder::class);
});

function venteAvecRetour(Warehouse $lieu, ?Customer $client, Product $produit, int $qte, float $pu, string $quand, float $paye): Sale
{
    $vente = Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client?->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $qte * $pu,
        'discount_percent' => 0,
        'total' => $qte * $pu,
        'paid_amount' => $paye,
        'payment_status' => $paye >= $qte * $pu ? 'paid' : ($paye > 0 ? 'partial' : 'unpaid'),
        'confirmed_at' => $quand,
    ]);
    $vente->lines()->create(['product_id' => $produit->id, 'quantity' => $qte, 'unit_price' => $pu, 'price_type_code' => 'detail', 'line_total' => $qte * $pu]);

    return $vente;
}

it('retrouve les ventes contenant l article, de la plus recente a la plus ancienne, avec le reste retournable', function (): void {
    $lieu = Warehouse::factory()->create();
    $adaptateur = Product::factory()->create(['name' => 'ADAPTER HDMI TO VGA WITH AUDIO', 'cost_price' => 40]);
    $ahmed = Customer::factory()->create(['warehouse_id' => $lieu->id, 'name' => 'Ahmed']);
    $samir = Customer::factory()->create(['warehouse_id' => $lieu->id, 'name' => 'Samir']);
    $v60 = venteAvecRetour($lieu, $ahmed, $adaptateur, 2, 60, '2026-11-16 09:00', 120);
    $v100 = venteAvecRetour($lieu, $samir, $adaptateur, 1, 100, '2026-11-16 11:00', 100);

    $user = grantUser(['sale.return', 'customer.view_all'], ['warehouse_id' => $lieu->id]);
    $ventes = test()->actingAs($user)->getJson("/api/v1/customer-returns/sales?product_id={$adaptateur->id}")->assertOk()->json('data');

    expect($ventes)->toHaveCount(2)
        ->and($ventes[0]['reference'])->toBe($v100->reference)
        ->and($ventes[0]['customer'])->toBe('Samir')
        ->and((float) $ventes[0]['unit_price'])->toBe(100.0)
        ->and($ventes[0]['returnable'])->toBe(1)
        ->and($ventes[1]['reference'])->toBe($v60->reference)
        ->and($ventes[1]['returnable'])->toBe(2);
});

it('retourne au prix de la vente choisie, plafonne a la quantite achetee, et le benefice le deduit', function (): void {
    $lieu = Warehouse::factory()->create();
    $adaptateur = Product::factory()->create(['cost_price' => 40]);
    $ahmed = Customer::factory()->create(['warehouse_id' => $lieu->id]);
    $samir = Customer::factory()->create(['warehouse_id' => $lieu->id]);
    $v60 = venteAvecRetour($lieu, $ahmed, $adaptateur, 2, 60, '2026-11-16 09:00', 120);
    venteAvecRetour($lieu, $samir, $adaptateur, 1, 100, '2026-11-16 11:00', 100);
    $ligne60 = $v60->lines()->firstOrFail();

    $user = grantUser(['sale.return', 'customer.view_all'], ['warehouse_id' => $lieu->id]);
    $avant = app(ProfitReportService::class)->totaux('2026-11-16', '2026-11-16');
    expect($avant['revenue'])->toBe(220.0)->and($avant['profit'])->toBe(100.0);

    // Trop : la vente à 60 DH n'en contenait que 2.
    test()->actingAs($user)->postJson('/api/v1/stock/return-multi', [
        'warehouse_id' => $lieu->id,
        'occurred_at' => '2026-11-16',
        'lines' => [['product_id' => $adaptateur->id, 'quantity' => 3, 'condition' => 'resellable', 'sale_line_id' => $ligne60->id]],
    ])->assertUnprocessable();
    expect(SaleReturn::query()->count())->toBe(0);

    // Un retour sur la vente à 60 DH : c'est 60 qui sort du chiffre, pas 100.
    test()->actingAs($user)->postJson('/api/v1/stock/return-multi', [
        'warehouse_id' => $lieu->id,
        'occurred_at' => '2026-11-16',
        'lines' => [['product_id' => $adaptateur->id, 'quantity' => 1, 'condition' => 'resellable', 'sale_line_id' => $ligne60->id]],
    ])->assertCreated();

    $retour = SaleReturn::query()->firstOrFail();
    expect((float) $retour->unit_price)->toBe(60.0)->and((float) $retour->amount)->toBe(60.0)
        ->and($retour->customer_id)->toBe($ahmed->id)
        // Vente déjà payée : rien à imputer, le remboursement est de la main à la main.
        ->and((float) $retour->credited_amount)->toBe(0.0)
        ->and(app(StockReaderInterface::class)->quantityFor($lieu->id, $adaptateur->id))->toBe(1);

    $apres = app(ProfitReportService::class)->totaux('2026-11-16', '2026-11-16');
    expect($apres['revenue'])->toBe(160.0)->and($apres['cost'])->toBe(80.0)->and($apres['profit'])->toBe(80.0)
        ->and($apres['documents'])->toBe(2);

    // Le reste retournable sur cette vente est passé à 1.
    $ventes = test()->actingAs($user)->getJson("/api/v1/customer-returns/sales?product_id={$adaptateur->id}")->json('data');
    expect(collect($ventes)->firstWhere('sale_line_id', $ligne60->id)['returnable'])->toBe(1);
});

it('impute l avoir sur une facture a credit et ne rend pas le cout d un article defectueux', function (): void {
    $lieu = Warehouse::factory()->create();
    $produit = Product::factory()->create(['cost_price' => 40]);
    $client = Customer::factory()->create(['warehouse_id' => $lieu->id, 'balance' => 200]);
    $vente = venteAvecRetour($lieu, $client, $produit, 2, 100, '2026-11-16 09:00', 0);
    $ligne = $vente->lines()->firstOrFail();

    $user = grantUser(['sale.return', 'customer.view_all'], ['warehouse_id' => $lieu->id]);
    test()->actingAs($user)->postJson('/api/v1/stock/return-multi', [
        'warehouse_id' => $lieu->id,
        'occurred_at' => '2026-11-16',
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'condition' => 'defective', 'sale_line_id' => $ligne->id]],
    ])->assertCreated();

    $vente->refresh();
    $retour = SaleReturn::query()->firstOrFail();
    expect((float) $retour->credited_amount)->toBe(100.0)
        ->and((float) $vente->paid_amount)->toBe(100.0)->and($vente->payment_status)->toBe('partial')
        ->and((float) $client->refresh()->balance)->toBe(100.0)
        // Défectueux : entré puis ressorti, le stock vendable ne bouge pas.
        ->and(app(StockReaderInterface::class)->quantityFor($lieu->id, $produit->id))->toBe(0);

    $totaux = app(ProfitReportService::class)->totaux('2026-11-16', '2026-11-16');
    // CA 200 − 100 ; coût 80 (l'article défectueux est perdu) ; bénéfice 20.
    expect($totaux['revenue'])->toBe(100.0)->and($totaux['cost'])->toBe(80.0)->and($totaux['profit'])->toBe(20.0);
});

it('ne montre a un responsable que les ventes de son lieu', function (): void {
    $sien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $produit = Product::factory()->create(['cost_price' => 10]);
    venteAvecRetour($sien, Customer::factory()->create(['warehouse_id' => $sien->id]), $produit, 1, 50, '2026-11-16 09:00', 50);
    venteAvecRetour($autre, Customer::factory()->create(['warehouse_id' => $autre->id]), $produit, 1, 50, '2026-11-16 10:00', 50);

    $responsable = grantUser(['sale.return'], ['warehouse_id' => $sien->id]);
    $ventes = test()->actingAs($responsable)->getJson("/api/v1/customer-returns/sales?product_id={$produit->id}")->assertOk()->json('data');
    expect($ventes)->toHaveCount(1)->and($ventes[0]['warehouse_id'])->toBe($sien->id);
});
