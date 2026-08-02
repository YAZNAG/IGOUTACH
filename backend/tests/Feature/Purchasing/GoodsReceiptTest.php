<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderLine;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;

beforeEach(function (): void {
    $this->seed(MovementTypeSeeder::class);
});

/**
 * Crée un BC "sent" avec une ligne de $quantity unités et retourne [order, line].
 *
 * @return array{0: PurchaseOrder, 1: PurchaseOrderLine}
 */
function makeReceivablePo(int $quantity = 10, string $statusCode = 'sent'): array
{
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->create(['status_id' => PurchaseOrderStatus::where('code', $statusCode)->firstOrFail()->id]);

    $line = $order->lines()->create(['product_id' => $product->id, 'quantity' => $quantity, 'position' => 0]);

    return [$order, $line];
}

it('crée un bon de réception numéroté BR- et incrémente le stock à la date saisie', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    $receivedAt = now()->subDays(2)->startOfMinute();

    $response = $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => $receivedAt->format('Y-m-d H:i:s'),
        'invoice_number' => 'FA-778',
        'notes' => 'Livraison conforme',
        'lines' => [
            ['purchase_order_line_id' => $line->id, 'quantity' => 6, 'unit_price' => 120.50],
        ],
    ])->assertCreated();

    expect($response->json('number'))->toMatch('/^BR-\d{4}-\d{4}$/');
    expect($response->json('invoice_number'))->toBe('FA-778');
    expect($response->json('lines.0.quantity'))->toBe(6);
    expect((float) $response->json('lines.0.unit_price'))->toBe(120.5);
    expect((float) $response->json('lines.0.line_total'))->toBe(723.0);

    // Stock incrémenté via le mécanisme existant.
    $reader = app(StockReaderInterface::class);
    expect($reader->quantityFor($order->warehouse_id, $line->product_id))->toBe(6);

    // Le mouvement porte la date de réception saisie.
    $this->assertDatabaseHas('stock_movements', [
        'warehouse_id' => $order->warehouse_id,
        'product_id' => $line->product_id,
        'quantity' => 6,
        'reference_type' => 'goods_receipt',
        'created_at' => $receivedAt->format('Y-m-d H:i:s'),
    ]);
});

it('recalcule le CMUP avec le prix réel de réception', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    // Stock existant : 10 unités à 10.00 DH.
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $order->warehouse_id,
        'product_id' => $line->product_id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);

    // Réception de 10 unités à 20.00 DH → CMUP = (10×10 + 10×20) / 20 = 15.00.
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 10, 'unit_price' => 20]],
    ])->assertCreated();

    $stock = Stock::withoutGlobalScopes()
        ->where('warehouse_id', $order->warehouse_id)
        ->where('product_id', $line->product_id)
        ->firstOrFail();

    expect((float) $stock->average_cost)->toBe(15.0);
    expect($stock->quantity)->toBe(20);
});

it('passe le statut à partially_received puis received', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 4, 'unit_price' => 50]],
    ])->assertCreated();

    expect($order->refresh()->status()->first()->code)->toBe('partially_received');
    expect($line->refresh()->received_quantity)->toBe(4);

    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 6, 'unit_price' => 50]],
    ])->assertCreated();

    expect($order->refresh()->status()->first()->code)->toBe('received');
    expect($line->refresh()->received_quantity)->toBe(10);
});

it('exige un prix unitaire strictement positif', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    // Sans prix → 422 (validation).
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 5]],
    ])->assertStatus(422);

    // Prix nul → 422.
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 5, 'unit_price' => 0]],
    ])->assertStatus(422);

    expect($line->refresh()->received_quantity)->toBe(0);
});

it('refuse la sur-réception sans motif et l\'accepte avec motif', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    // 12 > 10 sans motif → 422, rien n'est écrit.
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 12, 'unit_price' => 30]],
    ])->assertStatus(422);

    expect($line->refresh()->received_quantity)->toBe(0);
    $this->assertDatabaseCount('goods_receipts', 0);

    // Avec motif → accepté, statut received (tout le reliquat couvert).
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 12, 'unit_price' => 30, 'over_receipt_reason' => 'Cadeau fournisseur']],
    ])->assertCreated();

    expect($line->refresh()->received_quantity)->toBe(12);
    expect($order->refresh()->status()->first()->code)->toBe('received');
});

it('refuse la réception sur un bon en brouillon ou annulé', function (): void {
    $user = grantUser(['receipt.create']);

    foreach (['draft', 'cancelled'] as $statusCode) {
        [$order, $line] = makeReceivablePo(10, $statusCode);

        $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
            'received_at' => now()->format('Y-m-d H:i:s'),
            'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 5, 'unit_price' => 10]],
        ])->assertStatus(422);
    }

    $this->assertDatabaseCount('goods_receipts', 0);
});

it('liste les bons de réception filtrés par date de réception', function (): void {
    $user = grantUser(['receipt.create', 'receipt.view']);
    [$order, $line] = makeReceivablePo(10);

    // Deux réceptions à des dates différentes.
    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => '2026-07-01 09:00:00',
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 3, 'unit_price' => 100]],
    ])->assertCreated();

    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => '2026-07-20 09:00:00',
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 2, 'unit_price' => 110]],
    ])->assertCreated();

    // Sans filtre : 2 réceptions.
    $this->actingAs($user)->getJson('/api/v1/goods-receipts')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure(['data' => [['id', 'number', 'purchase_order', 'supplier', 'warehouse', 'received_at', 'invoice_number', 'lines_count', 'total_quantity', 'total_amount', 'created_by', 'created_at']], 'meta']);

    // Filtre par période : seule la réception du 20/07 ressort.
    $response = $this->actingAs($user)->getJson('/api/v1/goods-receipts?date_from=2026-07-10&date_to=2026-07-25')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    expect((float) $response->json('data.0.total_amount'))->toBe(220.0);
    expect($response->json('data.0.total_quantity'))->toBe(2);
    expect($response->json('data.0.purchase_order.number'))->toBe($order->number);
});

it('affiche le détail et le PDF d\'un bon de réception', function (): void {
    $user = grantUser(['receipt.create', 'receipt.view']);
    [$order, $line] = makeReceivablePo(10);

    $receiptId = $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'invoice_number' => 'FA-2026-42',
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 5, 'unit_price' => 40]],
    ])->assertCreated()->json('id');

    $detail = $this->actingAs($user)->getJson("/api/v1/goods-receipts/{$receiptId}")
        ->assertOk();

    expect($detail->json('lines.0.product.sku'))->not->toBeNull();
    expect((float) $detail->json('lines.0.line_total'))->toBe(200.0);
    expect((float) $detail->json('total_amount'))->toBe(200.0);

    $pdf = $this->actingAs($user)->get("/api/v1/goods-receipts/{$receiptId}/pdf");
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

it('refuse la liste des réceptions sans la permission receipt.view', function (): void {
    $user = grantUser(['receipt.create']);

    $this->actingAs($user)->getJson('/api/v1/goods-receipts')->assertForbidden();
});

it('enregistre un paiement intégral à la réception', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    $response = $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'payment_status' => 'paid',
        'lines' => [
            ['purchase_order_line_id' => $line->id, 'quantity' => 10, 'unit_price' => 20.00],
        ],
    ])->assertCreated();

    expect($response->json('payment_status'))->toBe('paid')
        ->and((float) $response->json('amount_paid'))->toBe(200.0)
        ->and((float) $response->json('remaining_amount'))->toBe(0.0);
});

it('enregistre un paiement partiel : le reste devient crédit fournisseur', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    $response = $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'payment_status' => 'partial',
        'amount_paid' => 80,
        'lines' => [
            ['purchase_order_line_id' => $line->id, 'quantity' => 10, 'unit_price' => 20.00],
        ],
    ])->assertCreated();

    expect($response->json('payment_status'))->toBe('partial')
        ->and((float) $response->json('amount_paid'))->toBe(80.0)
        ->and((float) $response->json('remaining_amount'))->toBe(120.0);
});

it('refuse un paiement partiel supérieur ou égal au total', function (): void {
    $user = grantUser(['receipt.create']);
    [$order, $line] = makeReceivablePo(10);

    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'payment_status' => 'partial',
        'amount_paid' => 200,
        'lines' => [
            ['purchase_order_line_id' => $line->id, 'quantity' => 10, 'unit_price' => 20.00],
        ],
    ])->assertStatus(422);
});
