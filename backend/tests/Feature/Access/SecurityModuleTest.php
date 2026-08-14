<?php

declare(strict_types=1);

use App\Domain\Access\Models\AuditLog;
use App\Domain\Settings\Models\PaymentMethod;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('refuse le journal d\'audit sans la permission', function () {
    $user = grantUser([]);

    $this->actingAs($user)->getJson('/api/v1/audit')->assertForbidden();
});

it('liste les entrées du journal d\'audit', function () {
    $user = grantUser(['audit.view']);
    AuditLog::create(['user_id' => $user->id, 'action' => 'user.created', 'module' => 'access', 'description' => 'Test']);

    $this->actingAs($user)->getJson('/api/v1/audit')
        ->assertOk()
        ->assertJsonPath('data.0.action', 'user.created');
});

it('lit et met à jour les paramètres généraux', function () {
    $user = grantUser(['settings.view', 'settings.manage']);

    $this->actingAs($user)->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.company.company_name', 'IGOUTECH');

    $this->actingAs($user)->putJson('/api/v1/settings', ['company_name' => 'Igoutech SARL', 'max_discount_percent' => 15])
        ->assertOk();

    $this->actingAs($user)->getJson('/api/v1/settings')
        ->assertJsonPath('data.company.company_name', 'Igoutech SARL')
        ->assertJsonPath('data.rules.max_discount_percent', 15);
});

it('gère les modes de paiement', function () {
    $user = grantUser(['payment_method.view', 'payment_method.manage']);

    $created = $this->actingAs($user)->postJson('/api/v1/payment-methods', [
        'code' => 'CASH', 'name' => 'Espèces', 'type' => 'cash',
    ])->assertCreated()->json('data');

    expect($created['code'])->toBe('CASH');

    $this->actingAs($user)->putJson("/api/v1/payment-methods/{$created['id']}", [
        'code' => 'CASH', 'name' => 'Espèces MAD', 'type' => 'cash',
    ])->assertOk()->assertJsonPath('data.name', 'Espèces MAD');

    $this->actingAs($user)->deleteJson("/api/v1/payment-methods/{$created['id']}")->assertOk();

    // On vérifie que CE mode a disparu, pas que la table est vide : des modes
    // sont désormais semés par migration, et compter les lignes ferait échouer
    // ce test à chaque nouveau mode livré.
    expect(PaymentMethod::query()->find($created['id']))->toBeNull();
});

it('rattache des utilisateurs à un lieu', function () {
    $admin = grantUser(['warehouse.assign_users']);
    $warehouse = Warehouse::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($admin)->postJson("/api/v1/warehouses/{$warehouse->id}/assign-users", [
        'user_ids' => [$a->id, $b->id],
    ])->assertOk();

    expect($a->refresh()->warehouse_id)->toBe($warehouse->id)
        ->and($b->refresh()->warehouse_id)->toBe($warehouse->id);
});

it('verrouille le compte après trop de tentatives échouées', function () {
    $user = User::factory()->create([
        'email' => 'lock@igoutech.ma',
        'password' => Hash::make('bon-mot-de-passe'),
        'is_active' => true,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', ['email' => 'lock@igoutech.ma', 'password' => 'mauvais'])
            ->assertStatus(422);
    }

    expect($user->refresh()->locked_until)->not->toBeNull();
});

it('refuse la connexion d\'un compte désactivé', function () {
    User::factory()->create([
        'email' => 'off@igoutech.ma',
        'password' => Hash::make('bon-mot-de-passe'),
        'is_active' => false,
    ]);

    $this->postJson('/api/v1/login', ['email' => 'off@igoutech.ma', 'password' => 'bon-mot-de-passe'])
        ->assertStatus(422);
});
