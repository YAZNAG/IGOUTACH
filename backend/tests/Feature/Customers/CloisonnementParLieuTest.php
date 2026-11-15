<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Payments\Models\Cheque;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Chaque lieu ne voit que ses clients : la liste, mais aussi tout ce qui en
 * découle (chiffres du client, écritures de crédit, chèques reçus).
 */
function responsableDe(Warehouse $lieu, array $permissions = []): App\Models\User
{
    return grantUser(array_merge(['customer.view', 'credit.view', 'cheque.view'], $permissions), ['warehouse_id' => $lieu->id]);
}

it('cache a un responsable les clients et les chiffres des autres lieux', function (): void {
    $sien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $chezLui = Customer::factory()->create(['warehouse_id' => $sien->id, 'name' => 'Client du lieu']);
    $ailleurs = Customer::factory()->create(['warehouse_id' => $autre->id, 'name' => 'Client ailleurs']);
    $responsable = responsableDe($sien);

    $liste = test()->actingAs($responsable)->getJson('/api/v1/customers?per_page=100')->assertOk()->json('data');
    expect(collect($liste)->pluck('id')->all())->toContain($chezLui->id)->not->toContain($ailleurs->id);

    test()->actingAs($responsable)->getJson("/api/v1/customers/{$ailleurs->id}")->assertForbidden();
    test()->actingAs($responsable)->getJson("/api/v1/customers/{$ailleurs->id}/stats")->assertForbidden();
    test()->actingAs($responsable)->getJson("/api/v1/customers/{$chezLui->id}/stats")->assertOk();
});

it('refuse de supprimer l ecriture de credit d un client d un autre lieu', function (): void {
    $sien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $ailleurs = Customer::factory()->create(['warehouse_id' => $autre->id]);
    $ecriture = CustomerLedgerEntry::query()->create([
        'customer_id' => $ailleurs->id,
        'type' => 'adjustment',
        'amount' => 10,
        'balance_after' => 10,
        'note' => 'saisie manuelle',
    ]);

    test()->actingAs(responsableDe($sien))->deleteJson("/api/v1/customer-ledger-entries/{$ecriture->id}")->assertForbidden();
    expect(CustomerLedgerEntry::query()->whereKey($ecriture->id)->exists())->toBeTrue();
});

it('ne liste a un responsable que les cheques de ses clients, et ceux sans client', function (): void {
    $sien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();
    $chezLui = Customer::factory()->create(['warehouse_id' => $sien->id]);
    $ailleurs = Customer::factory()->create(['warehouse_id' => $autre->id]);

    $base = ['instrument' => 'cheque', 'cheque_date' => '2026-11-10', 'amount' => 100, 'direction' => 'in', 'origin' => 'customer', 'status' => 'portfolio'];
    $duLieu = Cheque::query()->create($base + ['number' => 'CH-1', 'customer_id' => $chezLui->id]);
    $dAilleurs = Cheque::query()->create($base + ['number' => 'CH-2', 'customer_id' => $ailleurs->id]);
    $sansClient = Cheque::query()->create($base + ['number' => 'CH-3', 'direction' => 'out', 'origin' => 'company']);

    $ids = collect(test()->actingAs(responsableDe($sien))->getJson('/api/v1/cheques')->assertOk()->json('data'))->pluck('id')->all();
    expect($ids)->toContain($duLieu->id)->toContain($sansClient->id)->not->toContain($dAilleurs->id);

    $admin = grantUser(['cheque.view', 'customer.view_all']);
    $tous = collect(test()->actingAs($admin)->getJson('/api/v1/cheques')->assertOk()->json('data'))->pluck('id')->all();
    expect($tous)->toContain($dAilleurs->id);
});
