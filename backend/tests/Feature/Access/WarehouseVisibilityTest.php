<?php

declare(strict_types=1);

use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    $this->mien = Warehouse::factory()->create(['code' => 'MIEN']);
    $this->autre = Warehouse::factory()->create(['code' => 'AUTRE']);
});

it('ne liste que le lieu de l\'utilisateur mono-lieu', function (): void {
    $user = grantUser(['warehouse.view'], ['warehouse_id' => $this->mien->id]);

    $codes = collect($this->actingAs($user)->getJson('/api/v1/warehouses')->assertOk()->json('data'))
        ->pluck('code');

    // La liste alimente tous les sélecteurs de lieu : y laisser les autres
    // lieux les ferait réapparaître dans chaque filtre et formulaire.
    expect($codes)->toContain('MIEN')->and($codes)->not->toContain('AUTRE');
});

it('liste tous les lieux pour une vue globale', function (): void {
    $admin = grantUser(['warehouse.view', 'stock.view_global'], ['warehouse_id' => $this->mien->id]);

    $codes = collect($this->actingAs($admin)->getJson('/api/v1/warehouses')->json('data'))->pluck('code');

    expect($codes)->toContain('MIEN')->and($codes)->toContain('AUTRE');
});

it('refuse le détail, les comptes et la valorisation d\'un autre lieu', function (): void {
    $user = grantUser(['warehouse.view'], ['warehouse_id' => $this->mien->id]);

    $this->actingAs($user)->getJson("/api/v1/warehouses/{$this->autre->id}")->assertForbidden();
    $this->actingAs($user)->getJson("/api/v1/warehouses/{$this->autre->id}/users")->assertForbidden();
    $this->actingAs($user)->getJson("/api/v1/warehouses/{$this->autre->id}/summary")->assertForbidden();

    // Le sien reste accessible.
    $this->actingAs($user)->getJson("/api/v1/warehouses/{$this->mien->id}")->assertOk();
});
