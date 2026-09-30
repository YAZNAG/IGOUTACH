<?php

declare(strict_types=1);

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Models\SupplierPayment;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Journal des règlements fournisseurs, tous fournisseurs confondus.
 *
 * L'historique par fournisseur existait déjà ; ce journal répond à « qu'a-t-on
 * payé sur la période », question qu'aucun écran ne traitait.
 */
function reglementFournisseur(Supplier $fournisseur, float $montant, string $quand): SupplierPayment
{
    $receipt = GoodsReceipt::create([
        'number' => 'BR-J-'.uniqid(),
        'supplier_id' => $fournisseur->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'received_at' => $quand,
        'payment_status' => 'partial',
        'amount_paid' => number_format($montant, 2, '.', ''),
    ]);

    return SupplierPayment::create([
        'supplier_id' => $fournisseur->id,
        'goods_receipt_id' => $receipt->id,
        'amount' => number_format($montant, 2, '.', ''),
        'paid_at' => $quand,
    ]);
}

it('totalise les règlements de tous les fournisseurs', function (): void {
    $user = grantUser(['receipt.view']);
    $a = Supplier::factory()->create();
    $b = Supplier::factory()->create();

    reglementFournisseur($a, 1200, '2026-08-10');
    reglementFournisseur($b, 800, '2026-08-12');

    $data = $this->actingAs($user)->getJson('/api/v1/supplier-payments')->assertOk()->json('data');

    expect($data['count'])->toBe(2)
        ->and((float) $data['total_paid'])->toBe(2000.0)
        ->and($data['truncated'])->toBeFalse();
});

it('filtre par fournisseur', function (): void {
    $user = grantUser(['receipt.view']);
    $a = Supplier::factory()->create();
    $b = Supplier::factory()->create();

    reglementFournisseur($a, 1200, '2026-08-10');
    reglementFournisseur($b, 800, '2026-08-12');

    $data = $this->actingAs($user)
        ->getJson("/api/v1/supplier-payments?supplier_id={$a->id}")
        ->assertOk()->json('data');

    expect($data['count'])->toBe(1)
        ->and((float) $data['total_paid'])->toBe(1200.0);
});

it('filtre par période', function (): void {
    $user = grantUser(['receipt.view']);
    $a = Supplier::factory()->create();

    reglementFournisseur($a, 1200, '2026-07-10');
    reglementFournisseur($a, 800, '2026-08-12');

    $data = $this->actingAs($user)
        ->getJson('/api/v1/supplier-payments?date_from=2026-08-01&date_to=2026-08-31')
        ->assertOk()->json('data');

    expect($data['count'])->toBe(1)
        ->and((float) $data['total_paid'])->toBe(800.0);
});

it('refuse le journal sans la permission de voir les réceptions', function (): void {
    $user = grantUser(['sale.create']);

    $this->actingAs($user)->getJson('/api/v1/supplier-payments')->assertForbidden();
});
