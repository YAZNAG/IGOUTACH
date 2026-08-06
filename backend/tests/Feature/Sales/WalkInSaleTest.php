<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

it('vend à un client de passage : sans fiche, payé comptant, aucun crédit', function (): void {
    // Le lieu doit exister avant l'utilisateur : celui-ci y est rattaché.
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['sale.create'], ['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);

    // Création sans customer_id, prix saisi manuellement.
    $response = $this->actingAs($user)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'warehouse_id' => $warehouse->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 25]],
    ])->assertCreated();

    $saleId = $response->json('data.id');

    // Confirmation : sortie de stock + payé comptant automatiquement.
    $confirm = $this->actingAs($user)->postJson("/api/v1/sales/{$saleId}/confirm")->assertOk();

    expect($confirm->json('data.status'))->toBe('confirmed')
        ->and($confirm->json('data.payment_status'))->toBe('paid')
        ->and((float) $confirm->json('data.paid_amount'))->toBe(75.0)
        ->and($confirm->json('data.customer'))->toBeNull();

    // Stock sorti.
    expect((int) Stock::withoutGlobalScopes()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity'))->toBe(17);

    // Aucune écriture de crédit client.
    expect(CustomerLedgerEntry::count())->toBe(0);
});
