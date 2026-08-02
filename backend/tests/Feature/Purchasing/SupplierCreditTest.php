<?php

declare(strict_types=1);

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Catalog\Models\Product;
use App\Domain\Settings\Models\PaymentMethod;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Crée un BR avec une ligne de 10 × 20 DH (total 200), payé $paid.
 */
function makeCreditReceipt(float $paid = 0, string $status = 'unpaid'): GoodsReceipt
{
    $receipt = GoodsReceipt::create([
        'number' => 'BR-2026-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'supplier_id' => Supplier::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'received_at' => now(),
        'payment_status' => $status,
        'amount_paid' => number_format($paid, 2, '.', ''),
    ]);

    $receipt->lines()->create([
        'product_id' => Product::factory()->create()->id,
        'quantity' => 10,
        'unit_price' => '20.00',
        'position' => 0,
    ]);

    return $receipt;
}

it('liste les réceptions avec un crédit restant', function (): void {
    $user = grantUser(['receipt.view']);
    makeCreditReceipt(0, 'unpaid');
    makeCreditReceipt(50, 'partial');

    $response = $this->actingAs($user)->getJson('/api/v1/supplier-credits')->assertOk();

    expect($response->json('data.receipts_count'))->toBe(2)
        ->and((float) $response->json('data.total_due'))->toBe(350.0);
});

it('exclut les réceptions entièrement payées', function (): void {
    $user = grantUser(['receipt.view']);
    makeCreditReceipt(200, 'paid');

    $response = $this->actingAs($user)->getJson('/api/v1/supplier-credits')->assertOk();

    expect($response->json('data.receipts_count'))->toBe(0);
});

it('règle un crédit partiellement avec méthode de paiement', function (): void {
    $user = grantUser(['receipt.pay']);
    $receipt = makeCreditReceipt(0, 'unpaid');
    $method = PaymentMethod::create(['code' => 'virement', 'name' => 'Virement', 'is_active' => true]);

    $response = $this->actingAs($user)->postJson("/api/v1/goods-receipts/{$receipt->id}/pay", [
        'amount' => 80,
        'payment_method_id' => $method->id,
        'paid_at' => '2026-08-02',
        'notes' => 'Virement n°123',
    ])->assertCreated();

    expect($response->json('data.payment_status'))->toBe('partial')
        ->and((float) $response->json('data.amount_paid'))->toBe(80.0)
        ->and((float) $response->json('data.remaining_amount'))->toBe(120.0);

    $this->assertDatabaseHas('supplier_payments', [
        'goods_receipt_id' => $receipt->id,
        'amount' => '80.00',
        'payment_method_id' => $method->id,
    ]);
});

it('passe le statut à payé quand le crédit est soldé', function (): void {
    $user = grantUser(['receipt.pay']);
    $receipt = makeCreditReceipt(150, 'partial');

    $response = $this->actingAs($user)->postJson("/api/v1/goods-receipts/{$receipt->id}/pay", [
        'amount' => 50,
    ])->assertCreated();

    expect($response->json('data.payment_status'))->toBe('paid')
        ->and((float) $response->json('data.remaining_amount'))->toBe(0.0);
});

it('refuse un règlement supérieur au crédit restant', function (): void {
    $user = grantUser(['receipt.pay']);
    $receipt = makeCreditReceipt(150, 'partial');

    $this->actingAs($user)->postJson("/api/v1/goods-receipts/{$receipt->id}/pay", [
        'amount' => 100,
    ])->assertStatus(422);
});

it('refuse le règlement sans la permission receipt.pay', function (): void {
    $user = grantUser(['receipt.view']);
    $receipt = makeCreditReceipt(0, 'unpaid');

    $this->actingAs($user)->postJson("/api/v1/goods-receipts/{$receipt->id}/pay", [
        'amount' => 50,
    ])->assertForbidden();
});
