<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Journal des ventes : une ligne par journée, agrégée côté serveur.
 *
 * Le point sensible n'est pas le regroupement mais les totaux : ils portent
 * sur tout le filtre alors que le détail s'arrête à 120 journées. Un total
 * calculé sur l'écran mentirait dès la 121ᵉ.
 */
function venteJournal(Warehouse $lieu, float $total, float $paye, string $quand): Sale
{
    $vente = Sale::query()->create([
        'reference' => 'VT-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => Customer::factory()->create()->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $total,
        'discount_percent' => 0,
        'total' => $total,
        'paid_amount' => $paye,
        'payment_status' => $paye >= $total ? 'paid' : ($paye > 0 ? 'partial' : 'unpaid'),
        'confirmed_at' => $quand,
    ]);

    // « created_at » n'est pas assignable en masse : le journal groupe
    // dessus, une vente datée d'aujourd'hui ne prouverait rien.
    $vente->forceFill(['created_at' => $quand])->save();

    return $vente;
}

it('regroupe les ventes par journée avec le crédit du jour', function (): void {
    $lieu = Warehouse::factory()->create();
    $admin = grantUser(['sale.create', 'stock.view_global'], ['warehouse_id' => $lieu->id]);

    venteJournal($lieu, 1000, 1000, '2026-08-10 09:00:00');
    venteJournal($lieu, 500, 200, '2026-08-10 15:00:00');
    venteJournal($lieu, 300, 0, '2026-08-11 11:00:00');

    $data = $this->actingAs($admin)->getJson('/api/v1/sales/journal')->assertOk()->json('data');

    $jours = collect($data['days'])->keyBy('date');

    expect($jours['2026-08-10']['documents'])->toBe(2)
        ->and((float) $jours['2026-08-10']['revenue'])->toBe(1500.0)
        ->and((float) $jours['2026-08-10']['collected'])->toBe(1200.0)
        ->and((float) $jours['2026-08-10']['credit'])->toBe(300.0)
        ->and((float) $jours['2026-08-11']['credit'])->toBe(300.0);
});

it('exclut les devis du journal', function (): void {
    $lieu = Warehouse::factory()->create();
    $admin = grantUser(['sale.create', 'stock.view_global'], ['warehouse_id' => $lieu->id]);

    venteJournal($lieu, 400, 400, '2026-08-12 10:00:00');

    // Un devis n'engage rien : le compter gonflerait un chiffre d'affaires
    // qui n'a jamais été facturé.
    Sale::query()->create([
        'reference' => 'DV-JOURNAL',
        'type' => Sale::TYPE_QUOTE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => null,
        'warehouse_id' => $lieu->id,
        'subtotal' => 90000,
        'discount_percent' => 0,
        'total' => 90000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => '2026-08-12 10:00:00',
    ]);

    $data = $this->actingAs($admin)->getJson('/api/v1/sales/journal')->assertOk()->json('data');

    expect($data['totals']['documents'])->toBe(1)
        ->and((float) $data['totals']['revenue'])->toBe(400.0);
});

it('applique le filtre de période aux journées comme aux totaux', function (): void {
    $lieu = Warehouse::factory()->create();
    $admin = grantUser(['sale.create', 'stock.view_global'], ['warehouse_id' => $lieu->id]);

    venteJournal($lieu, 700, 700, '2026-07-20 10:00:00');
    venteJournal($lieu, 250, 250, '2026-08-05 10:00:00');

    $data = $this->actingAs($admin)
        ->getJson('/api/v1/sales/journal?date_from=2026-08-01&date_to=2026-08-31')
        ->assertOk()->json('data');

    expect($data['days'])->toHaveCount(1)
        ->and($data['totals']['documents'])->toBe(1)
        ->and((float) $data['totals']['revenue'])->toBe(250.0);
});

it('cloisonne le journal comme la liste des ventes', function (): void {
    $lieu = Warehouse::factory()->create();
    $vendeur = grantUser(['sale.create'], ['warehouse_id' => $lieu->id]);

    // Vente d'un autre vendeur, sur un client qui n'est pas le sien : le
    // journal ne doit pas la laisser deviner par ses totaux.
    venteJournal($lieu, 5000, 5000, '2026-08-14 10:00:00');

    $data = $this->actingAs($vendeur)->getJson('/api/v1/sales/journal')->assertOk()->json('data');

    expect($data['totals']['documents'])->toBe(0)
        ->and((float) $data['totals']['revenue'])->toBe(0.0);
});
