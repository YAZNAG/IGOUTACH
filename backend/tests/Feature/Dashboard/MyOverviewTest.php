<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->mien = Warehouse::factory()->create(['code' => 'MIEN']);
    $this->autre = Warehouse::factory()->create(['code' => 'AUTRE']);
});

function apercuVente(int $warehouseId, float $total, float $paye, ?int $customerId = null): Sale
{
    return Sale::withoutGlobalScopes()->create([
        'reference' => 'VT-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $customerId,
        'warehouse_id' => $warehouseId,
        'subtotal' => $total, 'discount_percent' => 0, 'total' => $total,
        'paid_amount' => $paye,
        'payment_status' => $paye >= $total ? 'paid' : ($paye > 0 ? 'partial' : 'unpaid'),
        'confirmed_at' => now(),
    ]);
}

it('donne les chiffres du jour et distingue ce qui reste a encaisser', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);

    apercuVente($this->mien->id, 1000, 1000);
    apercuVente($this->mien->id, 500, 200);

    $d = $this->actingAs($user)->getJson('/api/v1/me/overview')->assertOk()->json('data');

    expect($d['today']['count'])->toBe(2)
        ->and((float) $d['today']['revenue'])->toBe(1500.0)
        ->and((float) $d['today']['collected'])->toBe(1200.0)
        // Le chiffre qui dit si l'on vend a credit sans s'en rendre compte.
        ->and((float) $d['today']['on_credit'])->toBe(300.0);
});

it('ignore les chiffres des autres lieux', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);

    apercuVente($this->mien->id, 100, 100);
    apercuVente($this->autre->id, 99999, 99999);

    $d = $this->actingAs($user)->getJson('/api/v1/me/overview')->json('data');

    expect((float) $d['today']['revenue'])->toBe(100.0)
        ->and($d['warehouse']['code'])->toBe('MIEN');
});

it('consolide pour une vue multi-lieux', function (): void {
    $admin = grantUser(['stock.view', 'stock.view_global']);

    apercuVente($this->mien->id, 100, 100);
    apercuVente($this->autre->id, 400, 0);

    $d = $this->actingAs($admin)->getJson('/api/v1/me/overview')->json('data');

    expect((float) $d['today']['revenue'])->toBe(500.0)
        ->and($d['warehouse']['code'])->toBe('TOUS');
});

it('compte le stock, les ruptures et le sous-seuil de son lieu', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);

    $categorie = Category::factory()->create();
    $unite = Unit::factory()->create();
    $faire = fn (?int $seuil) => Product::factory()->create([
        'category_id' => $categorie->id, 'unit_id' => $unite->id, 'min_stock' => $seuil,
    ]);

    $ok = $faire(5);
    $bas = $faire(20);
    $rupture = $faire(null);

    foreach ([[$ok, 50], [$bas, 3], [$rupture, 0]] as [$p, $q]) {
        Stock::withoutGlobalScopes()->create([
            'warehouse_id' => $this->mien->id, 'product_id' => $p->id,
            'quantity' => $q, 'reserved_quantity' => 0, 'average_cost' => '10.00',
        ]);
    }

    $s = $this->actingAs($user)->getJson('/api/v1/me/overview')->json('data.stock');

    expect($s['units'])->toBe(53)
        ->and((float) $s['value'])->toBe(530.0)
        ->and($s['references'])->toBe(2)
        ->and($s['below_min'])->toBe(1)
        ->and($s['out_of_stock'])->toBe(1);
});

it('rapporte l\'encours des clients ayant achete dans ce lieu', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);

    $mien = Customer::factory()->create(['balance' => 2000, 'credit_limit' => 1000]);
    $ailleurs = Customer::factory()->create(['balance' => 5000, 'credit_limit' => 0]);

    apercuVente($this->mien->id, 100, 0, $mien->id);
    apercuVente($this->autre->id, 100, 0, $ailleurs->id);

    $c = $this->actingAs($user)->getJson('/api/v1/me/overview')->json('data.receivables');

    expect((float) $c['total'])->toBe(2000.0)
        ->and($c['customers'])->toBe(1)
        ->and($c['over_limit'])->toBe(1);
});

it('renvoie une serie de 14 jours, trous compris', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);
    apercuVente($this->mien->id, 300, 300);

    $serie = $this->actingAs($user)->getJson('/api/v1/me/overview')->json('data.daily');

    expect($serie)->toHaveCount(14)
        ->and((float) end($serie)['revenue'])->toBe(300.0)
        ->and((float) $serie[0]['revenue'])->toBe(0.0);
});

it('signale ce qui attend une action', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);

    apercuVente($this->mien->id, 500, 0);
    DB::table('inventories')->insert([
        'reference' => 'INV-A', 'warehouse_id' => $this->mien->id,
        'counted_at' => now(), 'status' => 'draft',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $p = $this->actingAs($user)->getJson('/api/v1/me/overview')->json('data.pending');

    expect($p['unpaid_sales'])->toBe(1)
        ->and($p['draft_inventories'])->toBe(1);
});
