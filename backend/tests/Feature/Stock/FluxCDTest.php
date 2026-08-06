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

/**
 * Alimente le stock via le flux complet BC → envoi → réception.
 */
function fluxStockViaReception($test, int $supplierId, int $warehouseId, int $productId, int $quantity, float $unitPrice): void
{
    $poId = $test->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplierId,
        'warehouse_id' => $warehouseId,
        'lines' => [['product_id' => $productId, 'quantity' => $quantity]],
    ])->json('id');

    $lineId = $test->getJson("/api/v1/purchase-orders/{$poId}")->json('lines.0.id');
    $test->postJson("/api/v1/purchase-orders/{$poId}/send");

    $test->postJson("/api/v1/purchase-orders/{$poId}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $lineId, 'quantity' => $quantity, 'unit_price' => $unitPrice]],
    ]);
}

it('crée puis réceptionne un bon de commande (stock + reliquat + statut)', function () {
    $user = grantUser(['purchase.view', 'purchase.create', 'receipt.create']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['cost_price' => 100]);
    $warehouse = fluxWarehouse();

    $create = $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10]],
    ])->assertCreated();

    $poId = $create->json('id');

    $lineId = $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$poId}")
        ->assertOk()->json('lines.0.id');

    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$poId}/send")->assertOk();

    // Réception partielle : 6 sur 10 → statut partially_received, reliquat 4.
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$poId}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $lineId, 'quantity' => 6, 'unit_price' => 120]],
    ])->assertCreated();

    $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$poId}")
        ->assertOk()
        ->assertJsonPath('status.code', 'partially_received')
        ->assertJsonPath('lines.0.remaining', 4);

    $this->assertDatabaseHas('stocks', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 6,
    ]);
});

it('confirme une facture : sortie de stock, créance et blocage au plafond', function () {
    // Le lieu doit exister avant l'utilisateur : celui-ci y est rattaché.
    $warehouse = fluxWarehouse();
    $user = grantUser(['sale.create', 'purchase.view', 'purchase.create', 'receipt.create'], ['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create();
    $customer = Customer::factory()->create(['credit_limit' => 500, 'balance' => 0]);
    $product = Product::factory()->create(['cost_price' => 100]);

    // Alimente le stock via une réception.
    fluxStockViaReception($this->actingAs($user), $supplier->id, $warehouse->id, $product->id, 10, 100);

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
    $warehouse = fluxWarehouse();
    $user = grantUser(['sale.create', 'payment.create', 'purchase.view', 'purchase.create', 'receipt.create'], ['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create();
    $customer = Customer::factory()->create(['credit_limit' => 10000, 'balance' => 0]);
    $product = Product::factory()->create(['cost_price' => 50]);

    fluxStockViaReception($this->actingAs($user), $supplier->id, $warehouse->id, $product->id, 5, 50);

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
    $warehouse = fluxWarehouse();
    $user = grantUser(['inventory.create'], ['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create();

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
    $user = grantUser(['transfer.create', 'transfer.receive', 'stock.view', 'purchase.view', 'purchase.create', 'receipt.create']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['cost_price' => 80]);
    $warehouses = Warehouse::factory()->count(2)->create();

    fluxStockViaReception($this->actingAs($user), $supplier->id, $warehouses[0]->id, $product->id, 4, 80);

    $transfer = $this->actingAs($user)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $warehouses[0]->id,
        'to_warehouse_id' => $warehouses[1]->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 3]],
    ])->assertCreated()->json('data.id');

    $this->assertDatabaseHas('stocks', ['warehouse_id' => $warehouses[0]->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->postJson("/api/v1/transfers/{$transfer}/receive")->assertOk();

    $this->assertDatabaseHas('stocks', ['warehouse_id' => $warehouses[1]->id, 'product_id' => $product->id, 'quantity' => 3]);
});
