<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

function metricsSale(int $warehouseId, float $total, string $paymentStatus, string $confirmedAt): Sale
{
    return Sale::withoutGlobalScopes()->create([
        'reference' => 'VT-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => null,
        'warehouse_id' => $warehouseId,
        'subtotal' => $total,
        'discount_percent' => 0,
        'total' => $total,
        'paid_amount' => $paymentStatus === 'paid' ? $total : 0,
        'payment_status' => $paymentStatus,
        'confirmed_at' => $confirmedAt,
    ]);
}

it('renvoie une série de 30 jours, trous compris', function (): void {
    $warehouse = Warehouse::factory()->create();
    $admin = grantUser(['stock.view_global'], ['warehouse_id' => $warehouse->id]);

    metricsSale($warehouse->id, 1200, 'paid', now()->toDateTimeString());

    $response = $this->actingAs($admin)->getJson('/api/v1/dashboard')->assertOk();

    $trend = $response->json('data.sales_trend');

    // Les jours sans vente doivent figurer à zéro : une courbe qui saute des
    // journées laisserait croire à une activité continue.
    expect($trend)->toHaveCount(30)
        ->and((float) end($trend)['revenue'])->toBe(1200.0)
        ->and((float) $trend[0]['revenue'])->toBe(0.0);
});

it('répartit le chiffre d\'affaires par état de règlement', function (): void {
    $warehouse = Warehouse::factory()->create();
    $admin = grantUser(['stock.view_global'], ['warehouse_id' => $warehouse->id]);

    metricsSale($warehouse->id, 1000, 'paid', now()->toDateTimeString());
    metricsSale($warehouse->id, 400, 'unpaid', now()->toDateTimeString());

    $mix = collect($this->actingAs($admin)->getJson('/api/v1/dashboard')->json('data.payment_mix'))
        ->keyBy('status');

    expect((float) $mix['paid']['amount'])->toBe(1000.0)
        ->and((float) $mix['unpaid']['amount'])->toBe(400.0)
        ->and((float) $mix['partial']['amount'])->toBe(0.0);
});

it('ne compte pas les devis dans le chiffre d\'affaires', function (): void {
    $warehouse = Warehouse::factory()->create();
    $admin = grantUser(['stock.view_global'], ['warehouse_id' => $warehouse->id]);

    metricsSale($warehouse->id, 5000, 'paid', now()->toDateTimeString());

    Sale::withoutGlobalScopes()->create([
        'reference' => 'DV-METRICS',
        'type' => Sale::TYPE_QUOTE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => null,
        'warehouse_id' => $warehouse->id,
        'subtotal' => 90000,
        'discount_percent' => 0,
        'total' => 90000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => now(),
    ]);

    $financial = $this->actingAs($admin)->getJson('/api/v1/dashboard')->json('data.financial');

    expect((float) $financial['revenue_month'])->toBe(5000.0)
        ->and($financial['sales_month'])->toBe(1);
});

it('agrège le stock par lieu avec sa valeur au coût moyen', function (): void {
    $admin = grantUser(['stock.view_global']);

    $response = $this->actingAs($admin)->getJson('/api/v1/dashboard')->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'summary',
            'financial' => ['revenue_month', 'sales_month', 'outstanding', 'stock_value', 'stock_value_purchase'],
            'sales_trend',
            'monthly_flow',
            'stock_by_warehouse',
            'top_products',
            'payment_mix',
            'stock',
        ],
    ]);

    expect($response->json('data.monthly_flow'))->toHaveCount(6);
});

it('valorise le stock au coût moyen et au prix d\'achat séparément', function (): void {
    $warehouse = Warehouse::factory()->create();
    $admin = grantUser(['stock.view_global'], ['warehouse_id' => $warehouse->id]);

    // Coût moyen 6, prix d'achat 8 : l'article a été réapprovisionné plus cher
    // que la moyenne qu'il traîne. Les deux valorisations doivent diverger,
    // sinon l'une des deux ne sert à rien.
    $product = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'cost_price' => 8,
        'sale_price' => 30,
    ]);

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '6.00',
    ]);

    $financial = $this->actingAs($admin)->getJson('/api/v1/dashboard')->json('data.financial');

    expect((float) $financial['stock_value'])->toBe(60.0)
        ->and((float) $financial['stock_value_purchase'])->toBe(80.0);
});
