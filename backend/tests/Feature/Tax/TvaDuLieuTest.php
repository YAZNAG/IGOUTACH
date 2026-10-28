<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Tax\Services\TaxeDuLieu;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * TVA portée par le lieu.
 *
 * Un dépôt déclaré à 20 % facture avec TVA ; les autres lieux continuent hors
 * taxe, sans qu'une seule de leurs ventes ne change de montant.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

function lieuTva(float $taux, bool $prixTtc = false): Warehouse
{
    return Warehouse::factory()->create([
        'vat_rate' => $taux,
        'prices_include_vat' => $prixTtc,
    ]);
}

function articleEnStock(Warehouse $lieu, int $quantite = 50, float $cout = 10): Product
{
    $produit = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'cost_price' => $cout,
    ]);

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $lieu->id,
        'product_id' => $produit->id,
        'quantity' => $quantite,
        'reserved_quantity' => 0,
        'average_cost' => number_format($cout, 2, '.', ''),
    ]);

    return $produit;
}

it('ajoute la TVA du lieu a une vente', function (): void {
    $lieu = lieuTva(20);
    $user = grantUser(['sale.create'], ['warehouse_id' => $lieu->id]);
    $produit = articleEnStock($lieu);

    $vente = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 2, 'unit_price' => 100]],
    ])->assertCreated();

    // 200 HT + 20 % = 240 TTC : c'est ce que le client doit.
    expect($vente->json('data.total'))->toEqual(240.0);

    $detail = test()->actingAs($user)->getJson('/api/v1/sales/'.$vente->json('data.id'))
        ->assertOk()->json('data');

    expect($detail['subtotal'])->toEqual(200.0)
        ->and($detail['tax_rate'])->toEqual(20.0)
        ->and($detail['tax_amount'])->toEqual(40.0)
        ->and($detail['total_ht'])->toEqual(200.0)
        ->and($detail['total'])->toEqual(240.0);
});

it('laisse les autres lieux hors taxe', function (): void {
    $lieu = lieuTva(0);
    $user = grantUser(['sale.create'], ['warehouse_id' => $lieu->id]);
    $produit = articleEnStock($lieu);

    $vente = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 2, 'unit_price' => 100]],
    ])->assertCreated();

    expect($vente->json('data.total'))->toEqual(200.0)
        ->and((float) Sale::query()->latest('id')->value('tax_amount'))->toBe(0.0);
});

it('extrait la TVA quand les prix sont saisis tout compris', function (): void {
    $lieu = lieuTva(20, prixTtc: true);
    $user = grantUser(['sale.create'], ['warehouse_id' => $lieu->id]);
    $produit = articleEnStock($lieu);

    $vente = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $lieu->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 120]],
    ])->assertCreated();

    // Le prix annoncé au client ne bouge pas : 120 reste 120, dont 20 de TVA.
    $detail = test()->actingAs($user)->getJson('/api/v1/sales/'.$vente->json('data.id'))
        ->assertOk()->json('data');

    expect($detail['total'])->toEqual(120.0)
        ->and($detail['tax_amount'])->toEqual(20.0)
        ->and($detail['total_ht'])->toEqual(100.0);
});

it('applique la TVA apres la remise', function (): void {
    $lieu = lieuTva(20);
    $user = grantUser(['sale.create', 'sale.discount'], ['warehouse_id' => $lieu->id]);
    $produit = articleEnStock($lieu);

    $vente = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $lieu->id,
        'discount_percent' => 10,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => 100]],
    ])->assertCreated();

    // 100 − 10 % = 90 HT, TVA 18, soit 108 TTC. Taxer avant la remise
    // ferait payer une TVA sur un prix que le client n'a jamais payé.
    expect($vente->json('data.total'))->toEqual(108.0)
        ->and((float) Sale::query()->latest('id')->value('tax_amount'))->toBe(18.0);
});

it('decompose un montant selon le reglage du lieu', function (): void {
    $service = app(TaxeDuLieu::class);

    $horsTaxe = lieuTva(20);
    $toutCompris = lieuTva(20, prixTtc: true);
    $sansTva = lieuTva(0);

    expect($service->decomposer(100, $horsTaxe->id))
        ->toMatchArray(['rate' => 20.0, 'ht' => 100.0, 'tva' => 20.0, 'ttc' => 120.0]);

    expect($service->decomposer(120, $toutCompris->id))
        ->toMatchArray(['rate' => 20.0, 'ht' => 100.0, 'tva' => 20.0, 'ttc' => 120.0]);

    expect($service->decomposer(100, $sansTva->id))
        ->toMatchArray(['rate' => 0.0, 'ht' => 100.0, 'tva' => 0.0, 'ttc' => 100.0]);
});
