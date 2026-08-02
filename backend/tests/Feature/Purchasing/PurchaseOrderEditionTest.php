<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;

function makeEditablePo(string $statusCode = 'draft'): PurchaseOrder
{
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->create(['status_id' => PurchaseOrderStatus::where('code', $statusCode)->firstOrFail()->id]);

    $order->lines()->create(['product_id' => $product->id, 'quantity' => 5, 'position' => 0]);

    return $order;
}

it('modifie un bon en brouillon avec remplacement intégral des lignes', function (): void {
    $user = grantUser(['purchase.create']);
    $order = makeEditablePo('draft');

    $newSupplier = Supplier::factory()->create();
    $newWarehouse = Warehouse::factory()->create();
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    $this->actingAs($user)->putJson("/api/v1/purchase-orders/{$order->id}", [
        'supplier_id' => $newSupplier->id,
        'warehouse_id' => $newWarehouse->id,
        'expected_at' => now()->addDays(3)->format('Y-m-d'),
        'notes' => 'Commande modifiée',
        'lines' => [
            ['product_id' => $productA->id, 'quantity' => 7],
            ['product_id' => $productB->id, 'quantity' => 2],
        ],
    ])->assertOk()
        ->assertJsonPath('supplier.id', $newSupplier->id)
        ->assertJsonPath('warehouse.id', $newWarehouse->id)
        ->assertJsonPath('notes', 'Commande modifiée');

    $order->refresh();
    expect($order->lines()->count())->toBe(2);
    expect($order->lines()->orderBy('position')->first()->product_id)->toBe($productA->id);
    expect($order->lines()->orderBy('position')->first()->quantity)->toBe(7);
});

it('refuse la modification d\'un bon envoyé', function (): void {
    $user = grantUser(['purchase.create']);
    $order = makeEditablePo('sent');
    $product = Product::factory()->create();

    $this->actingAs($user)->putJson("/api/v1/purchase-orders/{$order->id}", [
        'supplier_id' => $order->supplier_id,
        'warehouse_id' => $order->warehouse_id,
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertStatus(422);
});

it('génère le PDF d\'un bon envoyé', function (): void {
    $user = grantUser(['purchase.view']);
    $order = makeEditablePo('sent');

    $response = $this->actingAs($user)->get("/api/v1/purchase-orders/{$order->id}/pdf");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain("{$order->number}.pdf");
});

it('refuse le PDF d\'un bon en brouillon', function (): void {
    $user = grantUser(['purchase.view']);
    $order = makeEditablePo('draft');

    $this->actingAs($user)->getJson("/api/v1/purchase-orders/{$order->id}/pdf")
        ->assertStatus(422);
});
