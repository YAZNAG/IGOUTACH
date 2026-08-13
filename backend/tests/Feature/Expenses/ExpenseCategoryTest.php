<?php

declare(strict_types=1);

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Gestion des types de charge depuis le paramétrage.
 */
it('ne liste que les types actifs par defaut', function (): void {
    $user = grantUser(['expense.create']);
    ExpenseCategory::query()->create(['name' => 'Carburant', 'is_active' => true]);
    ExpenseCategory::query()->create(['name' => 'Ancien type', 'is_active' => false]);

    $noms = array_column(
        test()->actingAs($user)->getJson('/api/v1/expense-categories')->assertOk()->json('data'),
        'name',
    );

    // Proposer un type retiré du service ferait ressurgir ce qu'on a écarté.
    expect($noms)->toContain('Carburant')->not->toContain('Ancien type');
});

it('liste aussi les inactifs quand le parametrage les demande', function (): void {
    $user = grantUser(['expense.create']);
    ExpenseCategory::query()->create(['name' => 'Ancien type', 'is_active' => false]);

    $noms = array_column(
        test()->actingAs($user)->getJson('/api/v1/expense-categories?with_inactive=1')->assertOk()->json('data'),
        'name',
    );

    // Sans eux, un type désactivé serait impossible à retrouver, donc à réactiver.
    expect($noms)->toContain('Ancien type');
});

it('renomme un type de charge', function (): void {
    $user = grantUser(['expense.create', 'expense.approve']);
    $type = ExpenseCategory::query()->create(['name' => 'Restauration']);

    test()->actingAs($user)
        ->putJson("/api/v1/expense-categories/{$type->id}", ['name' => 'Frais de restauration'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Frais de restauration');
});

it('refuse un doublon de libelle', function (): void {
    $user = grantUser(['expense.create', 'expense.approve']);
    ExpenseCategory::query()->create(['name' => 'Loyer']);
    $autre = ExpenseCategory::query()->create(['name' => 'Carburant']);

    test()->actingAs($user)
        ->putJson("/api/v1/expense-categories/{$autre->id}", ['name' => 'Loyer'])
        ->assertStatus(422);
});

it('retire un type du service sans le supprimer', function (): void {
    $user = grantUser(['expense.create', 'expense.approve']);
    $type = ExpenseCategory::query()->create(['name' => 'Obsolete']);

    test()->actingAs($user)
        ->putJson("/api/v1/expense-categories/{$type->id}", ['is_active' => false])
        ->assertOk();

    expect(ExpenseCategory::query()->find($type->id)->is_active)->toBeFalse();
});

it('supprime un type que rien n\'utilise', function (): void {
    $user = grantUser(['expense.create', 'expense.approve']);
    $type = ExpenseCategory::query()->create(['name' => 'Jamais servi']);

    test()->actingAs($user)
        ->deleteJson("/api/v1/expense-categories/{$type->id}")
        ->assertNoContent();

    expect(ExpenseCategory::query()->find($type->id))->toBeNull();
});

it('refuse de supprimer un type deja utilise', function (): void {
    $lieu = Warehouse::factory()->create();
    $user = grantUser(['expense.create', 'expense.approve'], ['warehouse_id' => $lieu->id]);
    $type = ExpenseCategory::query()->create(['name' => 'Carburant']);

    Expense::query()->create([
        'expense_category_id' => $type->id,
        'warehouse_id' => $lieu->id,
        'user_id' => $user->id,
        'label' => 'Plein gasoil',
        'amount' => '450.00',
        'expense_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    // Supprimer laisserait des charges orphelines et fausserait les totaux par
    // type sur tout l'historique.
    test()->actingAs($user)
        ->deleteJson("/api/v1/expense-categories/{$type->id}")
        ->assertStatus(422);

    expect(ExpenseCategory::query()->find($type->id))->not->toBeNull();
});

it('interdit la gestion des types a qui ne valide pas les charges', function (): void {
    $user = grantUser(['expense.create']);
    $type = ExpenseCategory::query()->create(['name' => 'Loyer']);

    test()->actingAs($user)
        ->putJson("/api/v1/expense-categories/{$type->id}", ['name' => 'Autre'])
        ->assertForbidden();

    test()->actingAs($user)
        ->deleteJson("/api/v1/expense-categories/{$type->id}")
        ->assertForbidden();
});
