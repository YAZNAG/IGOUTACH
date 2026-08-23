<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Services\ProfitReportService;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Bénéfice de la période : ce qui reste une fois le coût déduit.
 */
function venteBenefice(
    Product $produit,
    Warehouse $lieu,
    ?Customer $client,
    float $prixUnitaire,
    int $quantite = 1,
): Sale {
    $total = $prixUnitaire * $quantite;

    $vente = Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client?->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $total,
        'discount_percent' => 0,
        'total' => $total,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => now(),
    ]);

    $vente->lines()->create([
        'product_id' => $produit->id,
        'quantity' => $quantite,
        'unit_price' => $prixUnitaire,
        'line_total' => $total,
    ]);

    return $vente;
}

it('deduit le cout du chiffre d\'affaires', function (): void {
    $lieu = Warehouse::factory()->create(['code' => 'PV-TEST']);
    $produit = Product::factory()->create(['cost_price' => 40]);

    venteBenefice($produit, $lieu, Customer::factory()->create(), 100, 3);

    $totaux = app(ProfitReportService::class)->totaux(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    // 3 x 100 vendus, 3 x 40 achetes : 300 - 120 = 180, soit 60 % de marge.
    expect($totaux['revenue'])->toEqual(300.0)
        ->and($totaux['cost'])->toEqual(120.0)
        ->and($totaux['profit'])->toEqual(180.0)
        ->and($totaux['margin_percent'])->toEqual(60.0);
});

it('signale la part vendue sans cout connu', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create();

    venteBenefice(Product::factory()->create(['cost_price' => 40]), $lieu, $client, 100);
    // Article jamais valorise : il affichera 100 % de marge.
    venteBenefice(Product::factory()->create(['cost_price' => 0]), $lieu, $client, 100);

    $totaux = app(ProfitReportService::class)->totaux(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    // La moitie du chiffre d'affaires repose sur un cout absent : sans cet
    // avertissement, le benefice se lirait comme acquis.
    expect($totaux['share_without_cost'])->toEqual(50.0)
        ->and($totaux['revenue_without_cost'])->toEqual(100.0)
        ->and($totaux['products_without_cost'])->toBe(1);
});

it('regroupe les ventes sans client sous le passage', function (): void {
    $lieu = Warehouse::factory()->create();
    $produit = Product::factory()->create(['cost_price' => 30]);

    venteBenefice($produit, $lieu, Customer::factory()->create(['name' => 'Client nomme']), 100);
    venteBenefice($produit, $lieu, null, 80);
    venteBenefice($produit, $lieu, null, 20);

    $lignes = app(ProfitReportService::class)->parClient(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    $passage = collect($lignes)->firstWhere('name', 'Client de passage');

    // Les deux ventes anonymes tiennent en une seule ligne : les eclater
    // n'apprendrait rien, les ecarter ferait manquer du benefice.
    expect($passage)->not->toBeNull()
        ->and($passage['revenue'])->toEqual(100.0)
        ->and($passage['documents'])->toBe(2)
        ->and(collect($lignes)->pluck('name'))->toContain('Client nomme');
});

it('rattache le benefice au fournisseur qui a livre l\'article', function (): void {
    $lieu = Warehouse::factory()->create();
    $produit = Product::factory()->create(['cost_price' => 60]);
    $fournisseur = Supplier::factory()->create(['name' => 'STE TEST']);

    $reception = GoodsReceipt::create([
        'number' => 'BR-BEN-1',
        'supplier_id' => $fournisseur->id,
        'warehouse_id' => $lieu->id,
        'received_at' => now()->subWeek(),
        'payment_status' => 'unpaid',
        'amount_paid' => '0.00',
    ]);
    $reception->lines()->create([
        'product_id' => $produit->id,
        'quantity' => 10,
        'unit_price' => '60.00',
        'position' => 0,
    ]);

    venteBenefice($produit, $lieu, Customer::factory()->create(), 100, 2);

    $lignes = app(ProfitReportService::class)->parFournisseur(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    // Le rattachement vient de la reception, pas du catalogue : la table de
    // referencement est vide en pratique.
    expect($lignes)->toHaveCount(1)
        ->and($lignes[0]['name'])->toBe('STE TEST')
        ->and($lignes[0]['profit'])->toEqual(80.0);
});

it('classe le benefice par lieu', function (): void {
    $fort = Warehouse::factory()->create(['code' => 'PV-FORT']);
    $faible = Warehouse::factory()->create(['code' => 'PV-FAIBLE']);
    $produit = Product::factory()->create(['cost_price' => 10]);
    $client = Customer::factory()->create();

    venteBenefice($produit, $fort, $client, 100, 5);
    venteBenefice($produit, $faible, $client, 30);

    $lignes = app(ProfitReportService::class)->parLieu(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    expect($lignes[0]['name'])->toBe('PV-FORT')
        ->and($lignes[0]['profit'])->toEqual(450.0)
        ->and($lignes[1]['name'])->toBe('PV-FAIBLE');
});
