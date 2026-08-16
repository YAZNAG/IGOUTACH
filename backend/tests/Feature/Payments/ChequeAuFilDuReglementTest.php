<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Payments\Models\Cheque;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Déclaration d'un effet au fil du règlement.
 *
 * Celui qui encaisse doit pouvoir déclarer le chèque qu'il reçoit sans détenir
 * en plus le droit d'administrer le portefeuille : c'est le même geste.
 */
it('declare le cheque du client avec l\'encaissement', function (): void {
    $user = grantUser(['payment.create', 'payment.view']);
    $client = Customer::factory()->create();

    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'payment_method_id' => modeCheque()->id,
        'amount' => 1500,
        'received_at' => now()->toDateString(),
        'cheque' => [
            'instrument' => Cheque::INSTRUMENT_CHEQUE,
            'number' => 'CH-2026-1',
            'cheque_date' => now()->toDateString(),
            'bank' => 'CIH',
            'origin' => Cheque::ORIGIN_CUSTOMER,
        ],
    ])->assertCreated();

    $cheque = Cheque::query()->where('number', 'CH-2026-1')->firstOrFail();

    expect($cheque->direction)->toBe(Cheque::DIRECTION_IN)
        ->and($cheque->origin)->toBe(Cheque::ORIGIN_CUSTOMER)
        ->and($cheque->customer_id)->toBe($client->id)
        ->and((float) $cheque->amount)->toBe(1500.0)
        ->and($cheque->status)->toBe(Cheque::STATUS_PORTFOLIO);
});

it('declare la traite d\'un tiers avec son nom', function (): void {
    $user = grantUser(['payment.create', 'payment.view']);
    $client = Customer::factory()->create();

    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'payment_method_id' => modeCheque()->id,
        'amount' => 800,
        'received_at' => now()->toDateString(),
        'cheque' => [
            'instrument' => Cheque::INSTRUMENT_TRAITE,
            'number' => 'TR-55',
            'cheque_date' => now()->addMonths(2)->toDateString(),
            'origin' => Cheque::ORIGIN_THIRD_PARTY,
            'drawer_name' => 'Hassan Amrani',
        ],
    ])->assertCreated();

    $effet = Cheque::query()->where('number', 'TR-55')->firstOrFail();

    expect($effet->instrument)->toBe(Cheque::INSTRUMENT_TRAITE)
        ->and($effet->drawer_name)->toBe('Hassan Amrani');
});

it('refuse un effet de tiers sans nom du signataire', function (): void {
    $user = grantUser(['payment.create', 'payment.view']);
    $client = Customer::factory()->create();

    // Sans ce nom, impossible de réclamer l'effet en cas de rejet.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'payment_method_id' => modeCheque()->id,
        'amount' => 800,
        'received_at' => now()->toDateString(),
        'cheque' => [
            'number' => 'CH-SANS-NOM',
            'cheque_date' => now()->toDateString(),
            'origin' => Cheque::ORIGIN_THIRD_PARTY,
        ],
    ])->assertStatus(422);

    expect(Cheque::query()->where('number', 'CH-SANS-NOM')->exists())->toBeFalse();
});

it('refuse un cheque client presente comme le notre', function (): void {
    $user = grantUser(['payment.create', 'payment.view']);
    $client = Customer::factory()->create();

    // Un encaissement ne peut pas porter notre propre chèque : c'est le
    // client qui paie, pas nous.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'payment_method_id' => modeCheque()->id,
        'amount' => 800,
        'received_at' => now()->toDateString(),
        'cheque' => [
            'number' => 'CH-INVERSE',
            'cheque_date' => now()->toDateString(),
            'origin' => Cheque::ORIGIN_OWN,
        ],
    ])->assertStatus(422);
});

it('declare notre cheque en reglant un fournisseur', function (): void {
    $user = grantUser(['receipt.pay', 'receipt.view']);

    $receipt = GoodsReceipt::create([
        'number' => 'BR-EFFET-1',
        'supplier_id' => Supplier::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'received_at' => now(),
        'payment_status' => 'unpaid',
        'amount_paid' => '0.00',
    ]);

    $receipt->lines()->create([
        'product_id' => Product::factory()->create()->id,
        'quantity' => 10,
        'unit_price' => '100.00',
        'position' => 0,
    ]);

    test()->actingAs($user)->postJson("/api/v1/goods-receipts/{$receipt->id}/pay", [
        'amount' => 500,
        'paid_at' => now()->toDateString(),
        'cheque' => [
            'number' => 'CH-FOURN-1',
            'cheque_date' => now()->addMonth()->toDateString(),
            'origin' => Cheque::ORIGIN_OWN,
        ],
    ])->assertCreated();

    $cheque = Cheque::query()->where('number', 'CH-FOURN-1')->firstOrFail();

    expect($cheque->direction)->toBe(Cheque::DIRECTION_OUT)
        ->and($cheque->origin)->toBe(Cheque::ORIGIN_OWN)
        ->and($cheque->supplier_id)->toBe($receipt->supplier_id);
});

it('refuse un encaissement sans mode de reglement', function (): void {
    $user = grantUser(['payment.create', 'payment.view']);
    $client = Customer::factory()->create();

    // Trois reglements ainsi enregistres ont suffi a rendre un compte client
    // incomprehensible : l'argent etait la, personne ne savait par ou.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 400,
        'received_at' => now()->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('payment_method_id');
});
