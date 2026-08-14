<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Sales\Models\Sale;

/**
 * Suppression d'une écriture du relevé client.
 */
it('supprime une ecriture saisie a la main et refait les soldes', function (): void {
    $user = grantUser(['credit.view']);
    $client = Customer::factory()->create(['balance' => 0]);
    $ledger = app(CustomerLedger::class);

    $ledger->record($client->id, CustomerLedgerEntry::TYPE_ADJUSTMENT, 500, note: 'A');
    $milieu = $ledger->record($client->id, CustomerLedgerEntry::TYPE_ADJUSTMENT, 300, note: 'B');
    $ledger->record($client->id, CustomerLedgerEntry::TYPE_ADJUSTMENT, 200, note: 'C');

    expect((float) $client->fresh()->balance)->toBe(1000.0);

    test()->actingAs($user)
        ->deleteJson("/api/v1/customer-ledger-entries/{$milieu->id}")
        ->assertNoContent();

    // Le solde du client ET la suite du relevé doivent suivre : laisser les
    // cumuls tels quels donnerait des lignes qui ne s'enchaînent plus.
    expect((float) $client->fresh()->balance)->toBe(700.0);

    $soldes = CustomerLedgerEntry::query()
        ->where('customer_id', $client->id)
        ->orderBy('id')
        ->pluck('balance_after')
        ->map(fn ($v): float => (float) $v)
        ->all();

    expect($soldes)->toBe([500.0, 700.0]);
});

it('refuse de supprimer une ecriture adossee a un document', function (): void {
    $user = grantUser(['credit.view']);
    $client = Customer::factory()->create(['balance' => 0]);

    $ecriture = app(CustomerLedger::class)->record(
        $client->id,
        CustomerLedgerEntry::TYPE_INVOICE,
        900,
        referenceType: Sale::class,
        referenceId: 1,
    );

    // La supprimer laisserait la vente en place et le relevé ne
    // correspondrait plus à rien.
    test()->actingAs($user)
        ->deleteJson("/api/v1/customer-ledger-entries/{$ecriture->id}")
        ->assertStatus(422);

    expect(CustomerLedgerEntry::query()->find($ecriture->id))->not->toBeNull()
        ->and((float) $client->fresh()->balance)->toBe(900.0);
});

it('interdit la suppression a qui ne consulte pas les credits', function (): void {
    $user = grantUser(['sale.create']);
    $client = Customer::factory()->create(['balance' => 0]);
    $ecriture = app(CustomerLedger::class)->record($client->id, CustomerLedgerEntry::TYPE_ADJUSTMENT, 100);

    test()->actingAs($user)
        ->deleteJson("/api/v1/customer-ledger-entries/{$ecriture->id}")
        ->assertForbidden();
});
