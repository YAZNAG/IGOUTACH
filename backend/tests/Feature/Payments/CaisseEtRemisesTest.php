<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Sales\Models\CashRemittance;
use App\Domain\Sales\Models\CashSession;
use App\Domain\Sales\Models\Payment;
use App\Domain\Settings\Models\PaymentMethod;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;

/**
 * Le tiroir-caisse : ce qui entre, ce qui sort, ce qui doit rester.
 */
function sessionOuverte(Warehouse $lieu, float $fonds, User $par): CashSession
{
    return CashSession::query()->create([
        'warehouse_id' => $lieu->id,
        'opened_by' => $par->id,
        'opened_at' => now()->subHours(2),
        'opening_amount' => $fonds,
        'status' => CashSession::STATUS_OPEN,
    ]);
}

it('deduit les charges payees en especes du solde attendu', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.manage'], ['warehouse_id' => $lieu->id]);
    $mode = modeEspeces();

    sessionOuverte($lieu, 1000, $user);

    Payment::query()->create([
        'reference' => 'ENC-1',
        'customer_id' => Customer::factory()->create()->id,
        'payment_method_id' => $mode->id,
        'amount' => 500,
        'received_at' => now(),
        'user_id' => $user->id,
    ]);

    Expense::query()->create([
        'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => 'Carburant'], ['is_active' => true])->id,
        'warehouse_id' => $lieu->id,
        'user_id' => $user->id,
        'label' => 'Carburant',
        'amount' => 200,
        'payment_method_id' => $mode->id,
        'payment_status' => 'paid',
        'paid_at' => now(),
        'expense_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    $cash = test()->actingAs($user)
        ->getJson("/api/v1/cash-sessions/current?warehouse_id={$lieu->id}")
        ->assertOk()->json('cash');

    // 1000 de fonds + 500 encaissés - 200 de charge = 1300.
    expect($cash['opening'])->toEqual(1000.0)
        ->and($cash['cash_in'])->toEqual(500.0)
        ->and($cash['cash_expenses'])->toEqual(200.0)
        ->and($cash['expected'])->toEqual(1300.0);
});

/**
 * Vente au client de passage : payee comptant a la validation, sans ligne de
 * reglement (un reglement exige une fiche client).
 */
function venteComptoir(Warehouse $lieu, float $montant, string $statut): void
{
    \App\Domain\Sales\Models\Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => \App\Domain\Sales\Models\Sale::TYPE_INVOICE,
        'status' => $statut,
        'customer_id' => null,
        'warehouse_id' => $lieu->id,
        'subtotal' => $montant,
        'discount_percent' => 0,
        'total' => $montant,
        'paid_amount' => $montant,
        'payment_status' => 'paid',
        'confirmed_at' => now(),
    ]);
}

it('compte les ventes au client de passage dans le tiroir', function (): void {
    // Regression : 72 DH d'une vente au comptoir manquaient au solde de
    // VEH / H-1 le 17/09/2026, faute de ligne de reglement.
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.manage'], ['warehouse_id' => $lieu->id]);
    $mode = modeEspeces();

    sessionOuverte($lieu, 0, $user);

    Payment::query()->create([
        'reference' => 'ENC-C',
        'customer_id' => Customer::factory()->create()->id,
        'payment_method_id' => $mode->id,
        'amount' => 1011,
        'received_at' => now(),
        'user_id' => $user->id,
    ]);
    venteComptoir($lieu, 72, 'confirmed');
    // Une vente annulee a rendu l'argent : elle ne compte pas.
    venteComptoir($lieu, 500, 'cancelled');

    $cash = test()->actingAs($user)
        ->getJson("/api/v1/cash-sessions/current?warehouse_id={$lieu->id}")
        ->assertOk()->json('cash');

    expect($cash['cash_in'])->toEqual(1083.0)
        ->and($cash['expected'])->toEqual(1083.0);
});

it('ignore les encaissements qui ne passent pas par le tiroir', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open'], ['warehouse_id' => $lieu->id]);

    sessionOuverte($lieu, 0, $user);

    $virement = PaymentMethod::firstOrCreate(
        ['code' => 'TRANSFER'],
        ['name' => 'Virement', 'type' => 'transfer', 'position' => 3, 'is_active' => true],
    );

    Payment::query()->create([
        'reference' => 'ENC-2',
        'customer_id' => Customer::factory()->create()->id,
        'payment_method_id' => $virement->id,
        'amount' => 9000,
        'received_at' => now(),
        'user_id' => $user->id,
    ]);

    $cash = test()->actingAs($user)
        ->getJson("/api/v1/cash-sessions/current?warehouse_id={$lieu->id}")
        ->assertOk()->json('cash');

    // Un virement n'a jamais touché le tiroir : le réclamer au responsable
    // serait lui demander un argent qu'il n'a pas eu entre les mains.
    expect($cash['expected'])->toEqual(0.0);
});

it('ouvre la journee sur le reste de la veille', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.manage'], ['warehouse_id' => $lieu->id]);

    // Hier : ouverte a 200, close avec 750 comptes dans le tiroir.
    $hier = sessionOuverte($lieu, 200, $user);
    test()->actingAs($user)
        ->postJson("/api/v1/cash-sessions/{$hier->id}/close", ['closing_amount' => 750])
        ->assertOk();

    // Le serveur propose ce reste avant meme d'ouvrir.
    $courant = test()->actingAs($user)
        ->getJson("/api/v1/cash-sessions/current?warehouse_id={$lieu->id}")
        ->assertOk();
    expect($courant->json('suggested_opening'))->toEqual(750.0)
        ->and($courant->json('last_closed.closing_amount'))->toEqual(750.0);

    // Ouvrir sans montant reprend ce reste : aucune journee ne repart de zero
    // sans qu'on l'ait voulu.
    $ouverte = test()->actingAs($user)
        ->postJson('/api/v1/cash-sessions/open', ['warehouse_id' => $lieu->id])
        ->assertCreated();

    expect($ouverte->json('data.opening_amount'))->toEqual(750.0);
});

it('detaille chaque journee close : entrees, charges, transferts', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.manage', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    $mode = modeEspeces();

    $session = sessionOuverte($lieu, 100, $user);

    Payment::query()->create([
        'reference' => 'ENC-J1',
        'customer_id' => Customer::factory()->create()->id,
        'payment_method_id' => $mode->id,
        'amount' => 900,
        'received_at' => now(),
        'user_id' => $user->id,
    ]);

    Expense::query()->create([
        'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => 'Carburant'], ['is_active' => true])->id,
        'warehouse_id' => $lieu->id,
        'user_id' => $user->id,
        'label' => 'Gasoil',
        'amount' => 100,
        'payment_method_id' => $mode->id,
        'payment_status' => 'paid',
        'paid_at' => now(),
        'expense_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 400,
    ])->assertCreated();

    test()->actingAs($user)
        ->postJson("/api/v1/cash-sessions/{$session->id}/close", ['closing_amount' => 500])
        ->assertOk();

    $journee = test()->actingAs($user)
        ->getJson("/api/v1/cash-sessions?warehouse_id={$lieu->id}")
        ->assertOk()->json('data.0');

    // 100 de fonds + 900 encaisses - 100 de charge - 400 transferes = 500.
    expect($journee['cash_in'])->toEqual(900.0)
        ->and($journee['cash_expenses'])->toEqual(100.0)
        ->and($journee['remitted'])->toEqual(400.0)
        ->and($journee['expected_amount'])->toEqual(500.0)
        ->and($journee['difference'])->toEqual(0.0);
});

it('remet une partie de la caisse a l\'administration', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);

    sessionOuverte($lieu, 2000, $user);

    $reponse = test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 1500,
        'note' => 'Remise du soir',
    ])->assertCreated();

    expect($reponse->json('data.status'))->toBe(CashRemittance::STATUS_PENDING)
        ->and($reponse->json('data.reference'))->toStartWith('RC-')
        // Le tiroir s'allège immédiatement : l'argent est parti.
        ->and($reponse->json('cash.remitted'))->toEqual(1500.0)
        ->and($reponse->json('cash.expected'))->toEqual(500.0);
});

it('refuse de remettre plus que ce que contient le tiroir', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);

    sessionOuverte($lieu, 300, $user);

    test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 800,
    ])->assertStatus(422);

    expect(CashRemittance::withoutGlobalScopes()->count())->toBe(0);
});

it('confirme la reception cote administration', function (): void {
    $lieu = Warehouse::factory()->create();
    $responsable = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    sessionOuverte($lieu, 1000, $responsable);

    $id = test()->actingAs($responsable)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 400,
    ])->assertCreated()->json('data.id');

    $admin = grantUser(['cash.remit', 'cash.remit_receive', 'stock.view_global']);

    $data = test()->actingAs($admin)
        ->postJson("/api/v1/cash-remittances/{$id}/receive")
        ->assertOk()->json('data');

    expect($data['status'])->toBe(CashRemittance::STATUS_RECEIVED)
        ->and($data['received_by'])->toBe($admin->name);
});

it('interdit au responsable de confirmer sa propre remise', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    sessionOuverte($lieu, 1000, $user);

    $id = test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 400,
    ])->assertCreated()->json('data.id');

    // Sans cette séparation, la confirmation ne vérifierait plus rien.
    test()->actingAs($user)
        ->postJson("/api/v1/cash-remittances/{$id}/receive")
        ->assertForbidden();
});

it('supprime une remise en attente mais pas une remise confirmee', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    sessionOuverte($lieu, 1000, $user);

    $id = test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 400,
    ])->assertCreated()->json('data.id');

    $admin = grantUser(['cash.remit', 'cash.remit_receive', 'stock.view_global']);
    test()->actingAs($admin)->postJson("/api/v1/cash-remittances/{$id}/receive")->assertOk();

    // La direction a compté cet argent : l'effacer ferait mentir son décompte.
    test()->actingAs($user)->deleteJson("/api/v1/cash-remittances/{$id}")->assertStatus(422);
});

it('cloture la caisse sur le solde reel, sorties comprises', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.manage', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    $mode = modeEspeces();

    $session = sessionOuverte($lieu, 1000, $user);

    Payment::query()->create([
        'reference' => 'ENC-3',
        'customer_id' => Customer::factory()->create()->id,
        'payment_method_id' => $mode->id,
        'amount' => 700,
        'received_at' => now(),
        'user_id' => $user->id,
    ]);

    test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 1200,
    ])->assertCreated();

    // 1000 + 700 - 1200 = 500 attendus dans le tiroir.
    $data = test()->actingAs($user)
        ->postJson("/api/v1/cash-sessions/{$session->id}/close", ['closing_amount' => 500])
        ->assertOk()->json('data');

    expect($data['expected_amount'])->toEqual(500.0)
        ->and($data['difference'])->toEqual(0.0);
});

it('joint une photo au transfert et la sert a l\'administration', function (): void {
    Illuminate\Support\Facades\Storage::fake('public');

    $lieu = Warehouse::factory()->create();
    $user = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    sessionOuverte($lieu, 1000, $user);

    $reponse = test()->actingAs($user)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 600,
        'proof' => Illuminate\Http\UploadedFile::fake()->image('recu.jpg'),
    ])->assertCreated();

    // Le justificatif accompagne le transfert : l'administration le regarde
    // avant de confirmer, sans quoi elle confirme sur parole.
    expect($reponse->json('data.proof_url'))->toContain('storage/cash-remittances');
    expect(Illuminate\Support\Facades\Storage::disk('public')->allFiles('cash-remittances'))->toHaveCount(1);
});

it('refuse un transfert : la somme revient au lieu, la trace reste', function (): void {
    $lieu = Warehouse::factory()->create();
    $responsable = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $lieu->id]);
    sessionOuverte($lieu, 1000, $responsable);

    $id = test()->actingAs($responsable)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $lieu->id,
        'amount' => 400,
    ])->assertCreated()->json('data.id');

    $admin = grantUser(['cash.remit', 'cash.remit_receive', 'stock.view_global']);

    $refus = test()->actingAs($admin)
        ->postJson("/api/v1/cash-remittances/{$id}/refuse", ['reason' => 'Somme non reçue'])
        ->assertOk()->json('data');

    expect($refus['status'])->toBe(CashRemittance::STATUS_REFUSED)
        ->and($refus['refused_by'])->toBe($admin->name)
        ->and($refus['refusal_reason'])->toBe('Somme non reçue');

    // Le tiroir retrouve ses 400 DH : l'argent n'a jamais quitté le lieu.
    $cash = test()->actingAs($responsable)
        ->getJson("/api/v1/cash-sessions/current?warehouse_id={$lieu->id}")
        ->assertOk()->json('cash');
    expect($cash['remitted'])->toEqual(0.0)
        ->and($cash['expected'])->toEqual(1000.0);

    // Mais la ligne demeure : un refus s'explique, il ne s'efface pas.
    expect(test()->actingAs($responsable)
        ->getJson('/api/v1/cash-remittances?status=refused')
        ->assertOk()->json('data'))->toHaveCount(1);
});

it('montre a la direction la caisse de chaque lieu', function (): void {
    $premier = Warehouse::factory()->create(['code' => 'AAA']);
    $second = Warehouse::factory()->create(['code' => 'BBB']);

    $responsable = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $premier->id]);
    sessionOuverte($premier, 800, $responsable);
    test()->actingAs($responsable)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $premier->id,
        'amount' => 300,
    ])->assertCreated();

    $admin = grantUser(['cash.remit', 'cash.remit_receive', 'stock.view_global', 'payment.create']);

    $vue = test()->actingAs($admin)->getJson('/api/v1/cash-sessions/overview')->assertOk();

    $ligne = collect($vue->json('data'))->firstWhere('code', 'AAA');
    expect($ligne['session_open'])->toBeTrue()
        ->and($ligne['expected'])->toEqual(500.0)
        ->and($ligne['pending_total'])->toEqual(300.0)
        ->and($ligne['pending_count'])->toBe(1)
        // Le second lieu figure aussi, tiroir vide : la direction voit tout.
        ->and(collect($vue->json('data'))->firstWhere('code', 'BBB')['expected'])->toEqual(0.0);
});

it('cloisonne les remises par lieu', function (): void {
    $mien = Warehouse::factory()->create();
    $autre = Warehouse::factory()->create();

    $voisin = grantUser(['payment.create', 'cash.open', 'cash.remit'], ['warehouse_id' => $autre->id]);
    sessionOuverte($autre, 1000, $voisin);
    test()->actingAs($voisin)->postJson('/api/v1/cash-remittances', [
        'warehouse_id' => $autre->id,
        'amount' => 300,
    ])->assertCreated();

    $moi = grantUser(['payment.create', 'cash.remit'], ['warehouse_id' => $mien->id]);

    expect(test()->actingAs($moi)->getJson('/api/v1/cash-remittances')->assertOk()->json('data'))
        ->toHaveCount(0);
});
