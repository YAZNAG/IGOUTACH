<?php

declare(strict_types=1);

use App\Domain\Warehouses\Models\Warehouse;

it('donne au responsable de lieu les alertes de son lieu, sans les indicateurs direction', function (): void {
    $warehouse = Warehouse::factory()->create();
    $user = grantUser(['stock.view'], ['warehouse_id' => $warehouse->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/alerts')->assertOk();

    $keys = collect($response->json('data'))->pluck('key');

    expect($response->json('scope'))->toBe('warehouse')
        ->and($keys)->toContain('low_stock')
        ->and($keys)->toContain('draft_inventories')
        // Marges et plafonds clients restent réservés à la direction.
        ->and($keys)->not->toContain('below_floor')
        ->and($keys)->not->toContain('over_credit');
});

it('donne à la direction les indicateurs transverses', function (): void {
    $admin = grantUser(['stock.view', 'report.consolidated', 'stock.view_global']);

    $response = $this->actingAs($admin)->getJson('/api/v1/alerts')->assertOk();

    $keys = collect($response->json('data'))->pluck('key');

    expect($response->json('scope'))->toBe('global')
        ->and($keys)->toContain('below_floor')
        ->and($keys)->toContain('over_credit')
        ->and($keys)->toContain('low_stock');
});

it('refuse les alertes sans stock.view', function (): void {
    $user = grantUser(['sale.create']);

    $this->actingAs($user)->getJson('/api/v1/alerts')->assertForbidden();
});
