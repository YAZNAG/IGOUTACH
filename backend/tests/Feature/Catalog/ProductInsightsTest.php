<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

function insightProduct(float $cost = 100): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'cost_price' => $cost,
        'sale_price' => 150,
    ]);
}

function insightSale(int $warehouseId, ?int $customerId, Product $product, int $qty, float $unitPrice): Sale
{
    $sale = Sale::withoutGlobalScopes()->create([
        'reference' => 'VT-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $customerId,
        'warehouse_id' => $warehouseId,
        'subtotal' => $qty * $unitPrice,
        'discount_percent' => 0,
        'total' => $qty * $unitPrice,
        'paid_amount' => $qty * $unitPrice,
        'payment_status' => 'paid',
        'confirmed_at' => now(),
    ]);

    DB::table('sale_lines')->insert([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => $qty,
        'unit_price' => $unitPrice,
        'line_total' => $qty * $unitPrice,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $sale;
}

it('calcule le volume vendu, le chiffre d\'affaires et la marge réelle', function (): void {
    $admin = grantUser(['product.view', 'product.view_cost_price']);
    $warehouse = Warehouse::factory()->create();
    $product = insightProduct(cost: 100);

    insightSale($warehouse->id, null, $product, 10, 150);

    $stats = $this->actingAs($admin)
        ->getJson("/api/v1/products/{$product->id}/statistics")
        ->assertOk()
        ->json('data');

    // 10 × 150 = 1500 encaissés, 10 × 100 = 1000 de coût, 500 de marge.
    expect((int) $stats['sales_volume'])->toBe(10)
        ->and((float) $stats['revenue'])->toBe(1500.0)
        ->and((float) $stats['cost_of_goods'])->toBe(1000.0)
        ->and((float) $stats['gross_margin'])->toBe(500.0)
        // Marge rapportée au CA, pas au coût : 500 / 1500.
        ->and((float) $stats['margin_percent'])->toBe(33.33)
        ->and((float) $stats['average_sale_price'])->toBe(150.0);
});

it('ignore les devis dans les statistiques de vente', function (): void {
    $admin = grantUser(['product.view', 'product.view_cost_price']);
    $warehouse = Warehouse::factory()->create();
    $product = insightProduct();

    $devis = Sale::withoutGlobalScopes()->create([
        'reference' => 'DV-INSIGHT',
        'type' => Sale::TYPE_QUOTE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => null,
        'warehouse_id' => $warehouse->id,
        'subtotal' => 9000, 'discount_percent' => 0, 'total' => 9000,
        'paid_amount' => 0, 'payment_status' => 'unpaid', 'confirmed_at' => now(),
    ]);

    DB::table('sale_lines')->insert([
        'sale_id' => $devis->id, 'product_id' => $product->id,
        'quantity' => 60, 'unit_price' => 150, 'line_total' => 9000,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $stats = $this->actingAs($admin)
        ->getJson("/api/v1/products/{$product->id}/statistics")
        ->json('data');

    expect((int) $stats['sales_volume'])->toBe(0)
        ->and((float) $stats['revenue'])->toBe(0.0);
});

it('renvoie une série mensuelle complète et la répartition par lieu', function (): void {
    $admin = grantUser(['product.view']);
    $warehouse = Warehouse::factory()->create(['code' => 'DEP-STAT']);
    $product = insightProduct();

    insightSale($warehouse->id, null, $product, 4, 150);

    $stats = $this->actingAs($admin)
        ->getJson("/api/v1/products/{$product->id}/statistics?period=6m")
        ->json('data');

    // Les mois sans vente restent dans la série, à zéro.
    expect($stats['monthly'])->toHaveCount(6)
        ->and($stats['by_warehouse'])->toHaveCount(1)
        ->and($stats['by_warehouse'][0]['warehouse'])->toBe('DEP-STAT')
        ->and((int) $stats['by_warehouse'][0]['quantity'])->toBe(4);
});

it('agrège l\'historique de l\'article à travers les modules', function (): void {
    $admin = grantUser(['product.view']);
    $warehouse = Warehouse::factory()->create();
    $product = insightProduct();
    $customer = Customer::factory()->create(['name' => 'Client Frise']);

    insightSale($warehouse->id, $customer->id, $product, 3, 150);

    $history = $this->actingAs($admin)
        ->getJson("/api/v1/products/{$product->id}/history")
        ->assertOk()
        ->json('data');

    expect($history)->not->toBeEmpty();

    $vente = collect($history)->firstWhere('module', 'sale');

    // Une vente sort du stock : la quantité doit être négative dans la frise.
    expect($vente)->not->toBeNull()
        ->and((int) $vente['quantity'])->toBe(-3)
        ->and($vente['party'])->toBe('Client Frise');
});

it('refuse les statistiques sans product.view', function (): void {
    $user = grantUser(['sale.create']);
    $product = insightProduct();

    $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/statistics")->assertForbidden();
    $this->actingAs($user)->getJson("/api/v1/products/{$product->id}/history")->assertForbidden();
});

it('renvoie le stock de l\'article sous forme agrégée, pas en liste brute', function (): void {
    $admin = grantUser(['product.view', 'stock.view_global']);
    $product = insightProduct();
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();

    \App\Domain\Stock\Models\Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $a->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 2, 'average_cost' => '20.00',
    ]);
    \App\Domain\Stock\Models\Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $b->id, 'product_id' => $product->id,
        'quantity' => 5, 'reserved_quantity' => 0, 'average_cost' => '20.00',
    ]);

    $data = $this->actingAs($admin)
        ->getJson("/api/v1/products/{$product->id}/stock")
        ->assertOk()
        ->json('data');

    // La fiche article additionne les lieux : sans totaux, l'en-tête affichait
    // « NaN unité(s) » puisqu'il lisait un tableau au lieu d'un agrégat.
    expect((int) $data['total_quantity'])->toBe(15)
        ->and((int) $data['total_reserved'])->toBe(2)
        ->and((int) $data['total_available'])->toBe(13)
        ->and((float) $data['total_valuation'])->toBe(300.0)
        ->and($data['locations'])->toHaveCount(2)
        ->and((int) $data['in_transit'])->toBe(0);
});
