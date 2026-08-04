<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

function exitMovement(array $overrides = []): StockMovement
{
    return StockMovement::create(array_merge([
        'warehouse_id' => Warehouse::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'movement_type_id' => MovementType::where('code', 'out')->firstOrFail()->id,
        'quantity' => -5,
        'unit_cost' => '20.00',
        'balance_after' => 10,
    ], $overrides));
}

it('liste les sorties (quantités négatives) avec totaux en valeur absolue', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    exitMovement(['quantity' => -5, 'unit_cost' => '20.00']);
    exitMovement(['quantity' => -3, 'unit_cost' => '10.00']);

    // Une entrée ne doit PAS apparaître.
    exitMovement([
        'movement_type_id' => MovementType::where('code', 'in')->firstOrFail()->id,
        'quantity' => 8,
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/stock/exits')->assertOk();

    expect($response->json('totals.lines_count'))->toBe(2)
        ->and($response->json('totals.total_quantity'))->toBe(8)
        ->and((float) $response->json('totals.total_value'))->toBe(130.0)
        ->and($response->json('data.0.quantity'))->toBeGreaterThan(0); // affiché en absolu
});

it('affiche le détail d\'une sortie valorisée au CMUP', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    $movement = exitMovement(['quantity' => -7, 'unit_cost' => '15.00', 'balance_after' => 3]);

    $response = $this->actingAs($user)->getJson("/api/v1/stock/exits/{$movement->id}")->assertOk();

    expect($response->json('data.quantity'))->toBe(7)
        ->and((float) $response->json('data.line_value'))->toBe(105.0)
        ->and($response->json('data.balance_after'))->toBe(3);
});

it('exporte les sorties en Excel et PDF', function (): void {
    $user = grantUser(['stock.view', 'stock.view_global']);
    exitMovement();

    $this->actingAs($user)
        ->get('/api/v1/stock/exits/export?format=xlsx')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($user)
        ->get('/api/v1/stock/exits/export?format=pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
