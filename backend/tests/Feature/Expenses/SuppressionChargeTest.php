<?php

declare(strict_types=1);

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Sales\Models\CashSession;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;

/**
 * Supprimer une charge réglée en espèces rend sa somme au tiroir.
 */
function chargeEspeces(Warehouse $lieu, User $user, float $montant, string $statut = 'paid'): Expense
{
    return Expense::query()->create([
        'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => 'Divers'], ['is_active' => true])->id,
        'warehouse_id' => $lieu->id,
        'user_id' => $user->id,
        'label' => 'Ligne a effacer',
        'amount' => $montant,
        'payment_method_id' => $statut === 'paid' ? modeEspeces()->id : null,
        'payment_status' => $statut,
        'paid_at' => $statut === 'paid' ? now()->toDateString() : null,
        'expense_date' => now()->toDateString(),
        'status' => 'approved',
    ]);
}

it('rend la somme a la caisse en supprimant une charge payee en especes', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(
        ['expense.create', 'expense.delete', 'payment.create', 'cash.open'],
        ['warehouse_id' => $lieu->id],
    );

    CashSession::query()->create([
        'warehouse_id' => $lieu->id,
        'opened_by' => $user->id,
        'opened_at' => now()->subHours(2),
        'opening_amount' => 1000,
        'status' => CashSession::STATUS_OPEN,
    ]);

    $charge = chargeEspeces($lieu, $user, 250);

    // Le tiroir est allégé tant que la charge existe.
    $avant = test()->actingAs($user)
        ->getJson("/api/v1/cash-sessions/current?warehouse_id={$lieu->id}")
        ->assertOk()->json('cash');
    expect($avant['cash_expenses'])->toEqual(250.0)
        ->and($avant['expected'])->toEqual(750.0);

    $reponse = test()->actingAs($user)
        ->deleteJson("/api/v1/expenses/{$charge->id}")
        ->assertOk();

    expect($reponse->json('cash.cash_expenses'))->toEqual(0.0)
        ->and($reponse->json('cash.expected'))->toEqual(1000.0)
        ->and(Expense::query()->find($charge->id))->toBeNull();
});

it('supprime une charge portee au credit sans toucher a la caisse', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(
        ['expense.create', 'expense.delete', 'payment.create', 'cash.open'],
        ['warehouse_id' => $lieu->id],
    );

    CashSession::query()->create([
        'warehouse_id' => $lieu->id,
        'opened_by' => $user->id,
        'opened_at' => now()->subHours(2),
        'opening_amount' => 500,
        'status' => CashSession::STATUS_OPEN,
    ]);

    $charge = chargeEspeces($lieu, $user, 300, 'unpaid');

    // Une charge due n'est jamais sortie du tiroir : rien n'y revient.
    $reponse = test()->actingAs($user)
        ->deleteJson("/api/v1/expenses/{$charge->id}")
        ->assertOk();

    expect($reponse->json('cash.expected'))->toEqual(500.0);
});

it('refuse d\'effacer une charge deja comptee dans une caisse cloturee', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(
        ['expense.create', 'expense.delete', 'payment.create', 'cash.manage'],
        ['warehouse_id' => $lieu->id],
    );

    $charge = chargeEspeces($lieu, $user, 120);

    CashSession::query()->create([
        'warehouse_id' => $lieu->id,
        'opened_by' => $user->id,
        'opened_at' => now()->subHours(5),
        'closed_at' => now(),
        'opening_amount' => 400,
        'closing_amount' => 280,
        'expected_amount' => 280,
        'difference' => 0,
        'status' => CashSession::STATUS_CLOSED,
    ]);

    // Quelqu'un a compté ce tiroir et signé l'écart : retirer la charge après
    // coup ferait mentir ce constat.
    test()->actingAs($user)
        ->deleteJson("/api/v1/expenses/{$charge->id}")
        ->assertStatus(422);

    expect(Expense::query()->find($charge->id))->not->toBeNull();
});

it('interdit la suppression a qui n\'a pas le droit', function (): void {
    $lieu = Warehouse::factory()->create();
    $auteur = grantUser(['expense.create'], ['warehouse_id' => $lieu->id]);
    $charge = chargeEspeces($lieu, $auteur, 90);

    test()->actingAs($auteur)
        ->deleteJson("/api/v1/expenses/{$charge->id}")
        ->assertForbidden();
});
