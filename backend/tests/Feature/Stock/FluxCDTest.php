<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;
use Database\Seeders\TransferStatusSeeder;

beforeEach(function () {
    $this->seed(MovementTypeSeeder::class);
    $this->seed(TransferStatusSeeder::class);
});

function fluxWarehouse(): Warehouse
{
    return Warehouse::factory()->create();
}

it('crée puis réceptionne un bon de commande (stock + reliquat + statut)', function () {
    $user = grantUser(['purchase.create', 'receipt.create']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['cost_price' => 100]);
    $warehouse = fluxWarehouse();

    $create = $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 120]],
    ])->assertCreated();

    $poId = $create->json('data.id');

    $lineId = $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$poId}")
        ->assertOk()->json('data.lines.0.id');

    // Réception partielle : 6 sur 10 → statut partial, reliquat 4.
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$poId}/receive", [
        'quantities' => [$lineId => 6],
    ])->assertOk()
        ->assertJsonPath('data.status', 'partial')
        ->assertJsonPath('data.lines.0.remaining', 4);

    $this->assertDatabaseHas('stocks', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 6,
    ]);
});

it('confirme une facture : sortie de stock, créance et blocage au plafond', function () {
    $user = grantUser(['sale.create', 'purchase.create', 'receipt.create']);
    $supplier = Supplier::factory()->create();
    $customer = Customer::factory()->create(['credit_limit' => 500, 'balance' => 0]);
    $product = Product::factory()->create(['cost_price' => 100]);
    $warehouse = fluxWarehouse();

    // Alimente le stock via une réception.
    $po = $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100]],
    ])->json('data.id');
    $lineId = $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$po}")->json('data.lines.0.id');
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$po}/receive", ['quantities' => [$lineId => 10]]);

    // Vente de 2 unités à 200 DH (prix explicite) = 400 DH ≤ plafond 500.
    $sale = $this->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 200]],
    ])->assertCreated()->json('data.id');

    $this->actingAs($user)->postJson("/api/v1/sales/{$sale}/confirm")->assertOk();

    $this->assertDatabaseHas('stocks', ['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 8]);
    $customer->refresh();
    expect((float) $customer->balance)->toBe(400.0)
        ->and($customer->is_blocked)->toBeFalse();

    // Deuxième facture de 200 DH → encours projeté 600 > plafond 500 : refusée.
    $sale2 = $this->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200]],
    ])->json('data.id');

    $this->actingAs($user)->postJson("/api/v1/sales/{$sale2}/confirm")->assertStatus(422);
});

it('encaisse un paiement : encours réduit et facture soldée', function () {
    $user = grantUser(['sale.create', 'payment.create', 'purchase.create', 'receipt.create']);
    $supplier = Supplier::factory()->create();
    $customer = Customer::factory()->create(['credit_limit' => 10000, 'balance' => 0]);
    $product = Product::factory()->create(['cost_price' => 50]);
    $warehouse = fluxWarehouse();

    $po = $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 50]],
    ])->json('data.id');
    $lineId = $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$po}")->json('data.lines.0.id');
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$po}/receive", ['quantities' => [$lineId => 5]]);

    $sale = $this->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 300]],
    ])->json('data.id');
    $this->actingAs($user)->postJson("/api/v1/sales/{$sale}/confirm")->assertOk();

    $this->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $customer->id,
        'amount' => 300,
        'sale_id' => $sale,
        'received_at' => now()->format('Y-m-d'),
    ])->assertCreated();

    $customer->refresh();
    expect((float) $customer->balance)->toBe(0.0);

    $this->actingAs($user)->getJson("/api/v1/sales/{$sale}")
        ->assertJsonPath('data.payment_status', 'paid');
});

it('exige un motif d\'écart à l\'enregistrement du comptage', function () {
    $user = grantUser(['inventory.create']);
    $product = Product::factory()->create();
    $warehouse = fluxWarehouse();

    $inventory = $this->actingAs($user)->postJson('/api/v1/inventories', [
        'warehouse_id' => $warehouse->id,
        'counted_at' => now()->format('Y-m-d'),
    ])->json('data.id');

    // Écart (compté 5, théorique 0) sans motif → 422.
    $this->actingAs($user)->putJson("/api/v1/inventories/{$inventory}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 5]],
    ])->assertStatus(422);

    // Avec motif → OK.
    $this->actingAs($user)->putJson("/api/v1/inventories/{$inventory}/lines", [
        'lines' => [['product_id' => $product->id, 'counted_quantity' => 5, 'reason' => 'Reprise initiale']],
    ])->assertOk();
});

it('transfère entre deux lieux avec le stock retiré à l\'envoi', function () {
    $user = grantUser(['transfer.create', 'transfer.receive', 'stock.view', 'purchase.create', 'receipt.create']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['cost_price' => 80]);
    $warehouses = Warehouse::factory()->count(2)->create();

    $po = $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouses[0]->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 80]],
    ])->json('data.id');
    $lineId = $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$po}")->json('data.lines.0.id');
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$po}/receive", ['quantities' => [$lineId => 4]]);

    $transfer = $this->actingAs($user)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $warehouses[0]->id,
        'to_warehouse_id' => $warehouses[1]->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 3]],
    ])->assertCreated()->json('data.id');

    $this->assertDatabaseHas('stocks', ['warehouse_id' => $warehouses[0]->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->postJson("/api/v1/transfers/{$transfer}/receive")->assertOk();

    $this->assertDatabaseHas('stocks', ['warehouse_id' => $warehouses[1]->id, 'product_id' => $product->id, 'quantity' => 3]);
});
