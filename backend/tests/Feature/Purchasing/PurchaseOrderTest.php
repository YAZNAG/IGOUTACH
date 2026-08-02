<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Actions\ApprovePurchaseOrderAction;
use App\Domain\Purchasing\Actions\CancelPurchaseOrderAction;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\ReceivePurchaseOrderLineAction;
use App\Domain\Purchasing\Actions\SendPurchaseOrderAction;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderLine;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;

it('crée un bon de commande en brouillon avec quantités seules', function (): void {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $action = app(CreatePurchaseOrderAction::class);

    $order = $action->execute(
        supplierId: $supplier->id,
        warehouseId: $warehouse->id,
        expectedAt: now()->addDays(7),
        notes: 'Commande test',
        createdBy: $user->id,
        lines: [
            ['product_id' => $product1->id, 'quantity' => 10],
            ['product_id' => $product2->id, 'quantity' => 5],
        ],
    );

    expect($order)
        ->id->toBeGreaterThan(0)
        ->number->toMatch('/^BC-\d{4}-\d{4}$/')
        ->supplier_id->toBe($supplier->id)
        ->warehouse_id->toBe($warehouse->id)
        ->notes->toBe('Commande test')
        ->created_by->toBe($user->id);

    expect($order->status()->first()->code)->toBe('draft');
    expect($order->lines)->toHaveCount(2);

    // Vérifier qu'aucun champ de prix n'existe
    $line = $order->lines()->first();
    expect($line)->not->toHaveProperty('unit_price')
        ->not->toHaveProperty('price');
});

it('génère un numéro auto-incrémenté (BC-YYYY-0001)', function (): void {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $action = app(CreatePurchaseOrderAction::class);

    $order1 = $action->execute(
        supplierId: $supplier->id,
        warehouseId: $warehouse->id,
        expectedAt: null,
        notes: null,
        createdBy: $user->id,
        lines: [['product_id' => $product->id, 'quantity' => 1]],
    );

    $order2 = $action->execute(
        supplierId: $supplier->id,
        warehouseId: $warehouse->id,
        expectedAt: null,
        notes: null,
        createdBy: $user->id,
        lines: [['product_id' => $product->id, 'quantity' => 1]],
    );

    expect($order1->number)->toMatch('/^BC-\d{4}-0001$/');
    expect($order2->number)->toMatch('/^BC-\d{4}-0002$/');
});

it('envoie un bon de commande (draft → sent)', function (): void {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id])
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'draft')->firstOrFail()->id]);

    $action = app(SendPurchaseOrderAction::class);
    $updatedOrder = $action->execute($order, requireApproval: false);

    expect($updatedOrder->status()->first()->code)->toBe('sent');
    expect($updatedOrder->ordered_at)->not->toBeNull();
});

it('envoie un bon en attente d\'approbation si requis (draft → pending_approval)', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id])
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'draft')->firstOrFail()->id]);

    $action = app(SendPurchaseOrderAction::class);
    $updatedOrder = $action->execute($order, requireApproval: true);

    expect($updatedOrder->status()->first()->code)->toBe('pending_approval');
});

it('approuve un bon en attente (pending_approval → sent)', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id])
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'pending_approval')->firstOrFail()->id]);

    $action = app(ApprovePurchaseOrderAction::class);
    $updatedOrder = $action->execute($order);

    expect($updatedOrder->status()->first()->code)->toBe('sent');
});

it('refuse l\'annulation si des articles ont été reçus', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id, 'received_quantity' => 5])
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'sent')->firstOrFail()->id]);

    $action = app(CancelPurchaseOrderAction::class);

    expect(fn () => $action->execute($order))
        ->toThrow(RuntimeException::class, 'Impossible d\'annuler un bon de commande avec des articles reçus.');
});

it('annule un bon de commande (sent → cancelled)', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id])
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'sent')->firstOrFail()->id]);

    $action = app(CancelPurchaseOrderAction::class);
    $updatedOrder = $action->execute($order);

    expect($updatedOrder->status()->first()->code)->toBe('cancelled');
});

it('reçoit des articles et met à jour le statut', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'sent')->firstOrFail()->id]);

    $line1 = $order->lines()->create(['product_id' => $product1->id, 'quantity' => 10, 'position' => 0]);
    $line2 = $order->lines()->create(['product_id' => $product2->id, 'quantity' => 5, 'position' => 1]);

    $action = app(ReceivePurchaseOrderLineAction::class);
    $updatedOrder = $action->execute($order, [
        $line1->id => 10,  // Reçu entièrement
        $line2->id => 3,   // Reçu partiellement
    ]);

    expect($updatedOrder->status()->first()->code)->toBe('partially_received');

    $line1->refresh();
    $line2->refresh();
    expect($line1->received_quantity)->toBe(10);
    expect($line2->received_quantity)->toBe(3);
});

it('marque comme reçu quand tous les articles sont reçus', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product1 = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'sent')->firstOrFail()->id]);

    $line1 = $order->lines()->create(['product_id' => $product1->id, 'quantity' => 10, 'position' => 0]);

    $action = app(ReceivePurchaseOrderLineAction::class);
    $updatedOrder = $action->execute($order, [
        $line1->id => 10,  // Reçu entièrement
    ]);

    expect($updatedOrder->status()->first()->code)->toBe('received');
});

it('refuse la réception si elle dépasse la quantité commandée', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $order = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'sent')->firstOrFail()->id]);

    $line = $order->lines()->create(['product_id' => $product->id, 'quantity' => 10, 'position' => 0]);

    $action = app(ReceivePurchaseOrderLineAction::class);

    expect(fn () => $action->execute($order, [$line->id => 15]))
        ->toThrow(RuntimeException::class);
});

it('calcule le reliquat correctement', function (): void {
    $line = new PurchaseOrderLine([
        'quantity' => 10,
        'received_quantity' => 3,
    ]);

    expect($line->remaining())->toBe(7);
});

it('retourne 0 pour le reliquat si tout est reçu', function (): void {
    $line = new PurchaseOrderLine([
        'quantity' => 10,
        'received_quantity' => 10,
    ]);

    expect($line->remaining())->toBe(0);
});

it('les scopes filtrent correctement', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $draftStatus = PurchaseOrderStatus::where('code', 'draft')->firstOrFail();
    $sentStatus = PurchaseOrderStatus::where('code', 'sent')->firstOrFail();

    $order1 = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id])
        ->create(['status_id' => $draftStatus->id]);

    $order2 = PurchaseOrder::factory()
        ->for($supplier)
        ->for($warehouse)
        ->hasLines(1, ['product_id' => $product->id])
        ->create(['status_id' => $sentStatus->id]);

    expect(PurchaseOrder::draft()->count())->toBe(1);
    expect(PurchaseOrder::sent()->count())->toBe(1);
    expect(PurchaseOrder::bySupplier($supplier->id)->count())->toBe(2);
});
