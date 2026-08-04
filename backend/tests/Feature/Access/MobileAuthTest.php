<?php

declare(strict_types=1);

it('connecte un utilisateur mobile et retourne un jeton + permissions', function (): void {
    $user = grantUser(['stock.view', 'sale.create']);
    $user->update(['password' => 'Secret!123']);

    $response = $this->postJson('/api/v1/mobile/login', [
        'email' => $user->email,
        'password' => 'Secret!123',
        'device_name' => 'test-phone',
    ])->assertOk();

    $token = $response->json('data.token');
    expect($token)->toBeString()->not->toBeEmpty()
        ->and($response->json('data.user.permissions'))->toContain('stock.view');

    // Le jeton donne accès à l'API.
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('refuse un mauvais mot de passe', function (): void {
    $user = grantUser([]);
    $user->update(['password' => 'Secret!123']);

    $this->postJson('/api/v1/mobile/login', [
        'email' => $user->email,
        'password' => 'mauvais',
        'device_name' => 'test-phone',
    ])->assertStatus(422);
});

it('révoque le jeton à la déconnexion', function (): void {
    $user = grantUser([]);
    $user->update(['password' => 'Secret!123']);

    $token = $this->postJson('/api/v1/mobile/login', [
        'email' => $user->email,
        'password' => 'Secret!123',
        'device_name' => 'test-phone',
    ])->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/mobile/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
