<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
    MovementType::firstOrCreate(['code' => 'adjustment'], ['name' => 'Ajustement', 'sign' => 0, 'affects_valuation' => false]);
});

function entryMovement(array $overrides = []): StockMovement
{
    return StockMovement::create(array_merge([
        'warehouse_id' => Warehouse::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'movement_type_id' => MovementType::where('code', 'in')->firstOrFail()->id,
        'quantity' => 5,
        'unit_cost' => '20.00',
        'balance_after' => 5,
    ], $overrides));
}

it('liste les entrées avec totaux sur l\'ensemble filtré', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    entryMovement(['quantity' => 5, 'unit_cost' => '20.00']);
    entryMovement(['quantity' => 3, 'unit_cost' => '10.00']);

    // Une sortie ne doit PAS apparaître.
    entryMovement([
        'movement_type_id' => MovementType::where('code', 'out')->firstOrFail()->id,
        'quantity' => -2,
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/stock/entries')->assertOk();

    expect($response->json('totals.lines_count'))->toBe(2)
        ->and($response->json('totals.total_quantity'))->toBe(8)
        ->and((float) $response->json('totals.total_value'))->toBe(130.0);
});

it('inclut les régularisations positives et exclut les négatives', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    $adjustmentId = MovementType::where('code', 'adjustment')->firstOrFail()->id;

    entryMovement(['movement_type_id' => $adjustmentId, 'quantity' => 4]);
    entryMovement(['movement_type_id' => $adjustmentId, 'quantity' => -4]);

    $response = $this->actingAs($user)->getJson('/api/v1/stock/entries')->assertOk();

    expect($response->json('totals.lines_count'))->toBe(1)
        ->and($response->json('data.0.type.code'))->toBe('adjustment');
});

it('filtre par date de mouvement, pas par date de saisie', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);

    entryMovement(['created_at' => '2026-07-10 09:00:00']);
    entryMovement(['created_at' => '2026-07-25 09:00:00']);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/stock/entries?date_from=2026-07-20&date_to=2026-07-31')
        ->assertOk();

    expect($response->json('totals.lines_count'))->toBe(1);
});

it('affiche le détail d\'une entrée', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    $movement = entryMovement(['quantity' => 7, 'unit_cost' => '15.00', 'balance_after' => 12]);

    $response = $this->actingAs($user)->getJson("/api/v1/stock/entries/{$movement->id}")->assertOk();

    expect($response->json('data.quantity'))->toBe(7)
        ->and((float) $response->json('data.unit_cost'))->toBe(15.0)
        ->and((float) $response->json('data.line_value'))->toBe(105.0)
        ->and($response->json('data.balance_after'))->toBe(12);
});

it('exporte la liste en Excel avec les filtres actifs', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    entryMovement();

    $this->actingAs($user)
        ->get('/api/v1/stock/entries/export?format=xlsx')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exporte la liste en PDF', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    entryMovement();

    $this->actingAs($user)
        ->get('/api/v1/stock/entries/export?format=pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
