<?php

declare(strict_types=1);

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Settings\Models\PaymentMethod;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Règlement d'une charge : payée sur-le-champ avec son mode, ou portée au
 * crédit puis soldée plus tard.
 */
function paiementEspeces(): PaymentMethod
{
    return PaymentMethod::query()->firstOrCreate(
        ['code' => 'CASH'],
        ['name' => 'Espèces', 'type' => 'cash', 'is_active' => true, 'position' => 1],
    );
}

/** @return array{0: \App\Models\User, 1: Warehouse, 2: ExpenseCategory} */
function chargeContexte(array $permissions = ['expense.create']): array
{
    $lieu = Warehouse::factory()->create();

    return [
        grantUser($permissions, ['warehouse_id' => $lieu->id]),
        $lieu,
        ExpenseCategory::query()->create(['name' => 'Frais de restauration '.uniqid()]),
    ];
}

it('enregistre une charge payee avec son mode de reglement', function (): void {
    [$user, $lieu, $type] = chargeContexte();
    $mode = paiementEspeces();

    $id = test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Repas equipe',
        'amount' => 250,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'paid',
        'payment_method_id' => $mode->id,
    ])->assertCreated()->json('data.id');

    $charge = Expense::query()->find($id);
    expect($charge->payment_status)->toBe('paid')
        ->and($charge->payment_method_id)->toBe($mode->id)
        ->and($charge->paid_at)->not->toBeNull();
});

it('exige le mode de reglement quand la charge est declaree payee', function (): void {
    [$user, $lieu, $type] = chargeContexte();

    // Une charge réglée sans mode ne se rapproche d'aucune caisse.
    test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Repas equipe',
        'amount' => 250,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'paid',
    ])->assertStatus(422);
});

it('porte une charge au credit sans mode de reglement', function (): void {
    [$user, $lieu, $type] = chargeContexte();

    $id = test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Repas a regler',
        'amount' => 300,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'unpaid',
    ])->assertCreated()->json('data.id');

    $charge = Expense::query()->find($id);
    expect($charge->payment_status)->toBe('unpaid')
        ->and($charge->payment_method_id)->toBeNull()
        ->and($charge->paid_at)->toBeNull();
});

it('ignore un mode transmis sur une charge a credit', function (): void {
    [$user, $lieu, $type] = chargeContexte();
    $mode = paiementEspeces();

    $id = test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Repas a regler',
        'amount' => 300,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'unpaid',
        'payment_method_id' => $mode->id,
    ])->assertCreated()->json('data.id');

    // Le conserver laisserait croire que la charge a été payée.
    expect(Expense::query()->find($id)->payment_method_id)->toBeNull();
});

it('regle plus tard une charge portee au credit', function (): void {
    [$user, $lieu, $type] = chargeContexte(['expense.create', 'expense.pay']);
    $mode = paiementEspeces();

    $id = test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Repas a regler',
        'amount' => 300,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'unpaid',
    ])->assertCreated()->json('data.id');

    test()->actingAs($user)
        ->postJson("/api/v1/expenses/{$id}/pay", ['payment_method_id' => $mode->id])
        ->assertOk()
        ->assertJsonPath('data.payment_status', 'paid');

    expect(Expense::query()->find($id)->paid_at)->not->toBeNull();
});

it('refuse de regler deux fois la meme charge', function (): void {
    [$user, $lieu, $type] = chargeContexte(['expense.create', 'expense.pay']);
    $mode = paiementEspeces();

    $id = test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Deja payee',
        'amount' => 100,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'paid',
        'payment_method_id' => $mode->id,
    ])->assertCreated()->json('data.id');

    test()->actingAs($user)
        ->postJson("/api/v1/expenses/{$id}/pay", ['payment_method_id' => $mode->id])
        ->assertStatus(422);
});

it('interdit le reglement a qui n\'a pas le droit de regler', function (): void {
    [$user, $lieu, $type] = chargeContexte();
    $mode = paiementEspeces();

    $id = test()->actingAs($user)->postJson('/api/v1/expenses', [
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'label' => 'Repas a regler',
        'amount' => 300,
        'expense_date' => now()->toDateString(),
        'payment_status' => 'unpaid',
    ])->assertCreated()->json('data.id');

    test()->actingAs($user)
        ->postJson("/api/v1/expenses/{$id}/pay", ['payment_method_id' => $mode->id])
        ->assertForbidden();
});

it('considere payees les charges saisies avant cette notion', function (): void {
    [$user, $lieu, $type] = chargeContexte();

    // Les anciennes charges n'avaient pas de notion de crédit : les marquer
    // dues ferait apparaître une dette qui n'a jamais existé.
    $charge = Expense::query()->create([
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'user_id' => $user->id,
        'label' => 'Charge historique',
        'amount' => '80.00',
        'expense_date' => now()->subMonth()->toDateString(),
        'status' => 'approved',
    ]);

    expect($charge->fresh()->payment_status)->toBe('paid');
});
