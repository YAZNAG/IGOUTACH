<?php

declare(strict_types=1);

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Expenses\Models\RecurringExpense;
use App\Domain\Expenses\Models\RecurringExpenseOccurrence;
use App\Domain\Expenses\Services\RecurringExpenseService;
use App\Domain\Settings\Models\PaymentMethod;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Carbon;

function chargeFixe(array $overrides = []): RecurringExpense
{
    return RecurringExpense::withoutGlobalScopes()->create(array_merge([
        'label' => 'Loyer dépôt',
        'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => 'Loyer'], [])->id,
        'warehouse_id' => null,
        'amount' => 4500,
        'day_of_month' => 5,
        'start_period' => '2026-06',
        'is_active' => true,
    ], $overrides));
}

it('génère une échéance par mois depuis le mois de départ', function (): void {
    $charge = chargeFixe(['start_period' => '2026-06']);

    $creees = app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-08-20'));

    // Juin, juillet, août.
    expect($creees)->toBe(3)
        ->and($charge->occurrences()->pluck('period')->sort()->values()->all())
        ->toBe(['2026-06', '2026-07', '2026-08']);
});

it('ne crée pas de doublon si la génération est rejouée', function (): void {
    chargeFixe();
    $service = app(RecurringExpenseService::class);

    $service->genererEcheances(Carbon::parse('2026-08-20'));
    $second = $service->genererEcheances(Carbon::parse('2026-08-20'));

    expect($second)->toBe(0)
        ->and(RecurringExpenseOccurrence::count())->toBe(3);
});

it('ramène le jour d\'échéance au dernier jour des mois plus courts', function (): void {
    $charge = chargeFixe(['day_of_month' => 31, 'start_period' => '2026-02']);

    app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-02-15'));

    // Février 2026 compte 28 jours : « le 31 » ne doit pas déborder sur mars.
    expect($charge->occurrences()->first()->due_date->toDateString())->toBe('2026-02-28');
});

it('s\'arrête à la période de fin', function (): void {
    $charge = chargeFixe(['start_period' => '2026-06', 'end_period' => '2026-07']);

    app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-12-01'));

    expect($charge->occurrences()->count())->toBe(2);
});

it('fige le montant à la création de l\'échéance', function (): void {
    $charge = chargeFixe(['start_period' => '2026-06', 'amount' => 4500]);
    $service = app(RecurringExpenseService::class);
    $service->genererEcheances(Carbon::parse('2026-06-10'));

    // Hausse du loyer : les mois déjà générés gardent l'ancien montant.
    $charge->update(['amount' => 5000]);
    $service->genererEcheances(Carbon::parse('2026-07-10'));

    $montants = $charge->occurrences()->orderBy('period')->pluck('amount')->map(fn ($m) => (float) $m);
    expect($montants->all())->toBe([4500.0, 5000.0]);
});

it('reste en attente tant qu\'elle n\'est pas réglée, puis crée la charge', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['expense.create', 'expense.approve', 'expense.recurring_manage'], ['warehouse_id' => $warehouse->id]);
    $charge = chargeFixe();
    $mode = PaymentMethod::firstOrCreate(['code' => 'CASH'], ['name' => 'Espèces', 'type' => 'cash', 'is_active' => true]);

    app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-08-20'));
    $echeance = $charge->occurrences()->orderBy('period')->first();

    expect($echeance->status)->toBe(RecurringExpenseOccurrence::STATUS_PENDING);

    $this->actingAs($user)
        ->postJson("/api/v1/recurring-expense-occurrences/{$echeance->id}/pay", [
            'payment_method_id' => $mode->id,
            'paid_at' => '2026-08-20',
        ])->assertOk()->assertJsonPath('data.status', 'paid');

    $echeance->refresh();

    // Le règlement doit peser dans le journal des charges, pas seulement
    // basculer un statut.
    expect($echeance->expense_id)->not->toBeNull()
        ->and((float) Expense::withoutGlobalScopes()->find($echeance->expense_id)->amount)->toBe(4500.0)
        ->and($echeance->payment_method_id)->toBe($mode->id);
});

it('refuse de régler deux fois la même échéance', function (): void {
    $user = grantUser(['expense.create', 'expense.approve']);
    $charge = chargeFixe();
    app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-08-20'));
    $echeance = $charge->occurrences()->first();

    $this->actingAs($user)->postJson("/api/v1/recurring-expense-occurrences/{$echeance->id}/pay", [])->assertOk();
    $this->actingAs($user)->postJson("/api/v1/recurring-expense-occurrences/{$echeance->id}/pay", [])->assertStatus(422);

    expect(Expense::withoutGlobalScopes()->count())->toBe(1);
});

it('signale les échéances dues dans les alertes', function (): void {
    $user = grantUser(['stock.view', 'expense.create']);
    chargeFixe(['start_period' => '2026-06', 'amount' => 4500]);
    app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-08-20'));

    $alerte = collect($this->actingAs($user)->getJson('/api/v1/alerts')->json('data'))
        ->firstWhere('key', 'recurring_expenses_due');

    expect($alerte)->not->toBeNull()
        ->and($alerte['count'])->toBeGreaterThan(0);
});

it('désactive au lieu de supprimer une charge déjà réglée', function (): void {
    $user = grantUser(['expense.create', 'expense.approve', 'expense.recurring_manage']);
    $charge = chargeFixe();
    app(RecurringExpenseService::class)->genererEcheances(Carbon::parse('2026-08-20'));
    $echeance = $charge->occurrences()->first();
    $this->actingAs($user)->postJson("/api/v1/recurring-expense-occurrences/{$echeance->id}/pay", [])->assertOk();

    $this->actingAs($user)->deleteJson("/api/v1/recurring-expenses/{$charge->id}")->assertOk();

    // Supprimer effacerait des règlements de l'historique.
    expect(RecurringExpense::withoutGlobalScopes()->find($charge->id))->not->toBeNull()
        ->and(RecurringExpense::withoutGlobalScopes()->find($charge->id)->is_active)->toBeFalse();
});

it('exige la permission dédiée pour créer une charge fixe', function (): void {
    $user = grantUser(['expense.create']);

    $this->actingAs($user)->postJson('/api/v1/recurring-expenses', [
        'label' => 'Test', 'amount' => 100, 'day_of_month' => 1, 'start_period' => '2026-08',
    ])->assertForbidden();
});
