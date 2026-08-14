<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Payments\Models\Cheque;
use App\Domain\Purchasing\Models\Supplier;

/**
 * Traite (lettre de change) : même effet de commerce qu'un chèque, mêmes
 * champs, même cycle. Seul le nom de l'instrument change.
 */
it('enregistre une traite recue d\'un client', function (): void {
    $user = grantUser(['cheque.view', 'cheque.manage']);
    $client = Customer::factory()->create();

    $data = test()->actingAs($user)->postJson('/api/v1/cheques', [
        'instrument' => Cheque::INSTRUMENT_TRAITE,
        'number' => 'TR-99001',
        'cheque_date' => now()->addMonth()->toDateString(),
        'amount' => 4500,
        'bank' => 'BMCE',
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_CUSTOMER,
        'customer_id' => $client->id,
    ])->assertCreated()->json('data');

    expect($data['instrument'])->toBe('traite')
        ->and($data['number'])->toBe('TR-99001');
});

it('conserve le cheque comme effet par defaut', function (): void {
    $user = grantUser(['cheque.view', 'cheque.manage']);
    $client = Customer::factory()->create();

    // Les enregistrements existants n'indiquaient pas l'instrument : ils
    // doivent rester des chèques.
    $data = test()->actingAs($user)->postJson('/api/v1/cheques', [
        'number' => 'CH-42',
        'cheque_date' => now()->toDateString(),
        'amount' => 800,
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_CUSTOMER,
        'customer_id' => $client->id,
    ])->assertCreated()->json('data');

    expect($data['instrument'])->toBe('cheque');
});

it('exige le nom du signataire sur une traite de tiers', function (): void {
    $user = grantUser(['cheque.view', 'cheque.manage']);
    $client = Customer::factory()->create();

    // Sans ce nom, impossible de réclamer l'effet en cas de rejet.
    test()->actingAs($user)->postJson('/api/v1/cheques', [
        'instrument' => Cheque::INSTRUMENT_TRAITE,
        'number' => 'TR-99002',
        'cheque_date' => now()->toDateString(),
        'amount' => 1200,
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_THIRD_PARTY,
        'customer_id' => $client->id,
    ])->assertStatus(422);
});

it('enregistre une traite remise a un fournisseur depuis notre carnet', function (): void {
    $user = grantUser(['cheque.view', 'cheque.manage']);
    $fournisseur = Supplier::factory()->create();

    $data = test()->actingAs($user)->postJson('/api/v1/cheques', [
        'instrument' => Cheque::INSTRUMENT_TRAITE,
        'number' => 'TR-77',
        'cheque_date' => now()->addMonths(2)->toDateString(),
        'amount' => 9000,
        'direction' => Cheque::DIRECTION_OUT,
        'origin' => Cheque::ORIGIN_OWN,
        'supplier_id' => $fournisseur->id,
    ])->assertCreated()->json('data');

    expect($data['instrument'])->toBe('traite')
        ->and($data['direction'])->toBe('out')
        ->and($data['origin'])->toBe('own');
});

it('filtre la liste par type d\'effet', function (): void {
    $user = grantUser(['cheque.view', 'cheque.manage']);
    $client = Customer::factory()->create();

    foreach ([Cheque::INSTRUMENT_CHEQUE, Cheque::INSTRUMENT_TRAITE] as $i => $effet) {
        test()->actingAs($user)->postJson('/api/v1/cheques', [
            'instrument' => $effet,
            'number' => 'EF-'.$i,
            'cheque_date' => now()->toDateString(),
            'amount' => 100,
            'direction' => Cheque::DIRECTION_IN,
            'origin' => Cheque::ORIGIN_CUSTOMER,
            'customer_id' => $client->id,
        ])->assertCreated();
    }

    $traites = test()->actingAs($user)
        ->getJson('/api/v1/cheques?instrument=traite')
        ->assertOk()->json('data');

    expect($traites)->toHaveCount(1)
        ->and($traites[0]['instrument'])->toBe('traite');
});
