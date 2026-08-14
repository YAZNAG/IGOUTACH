<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\PaymentAllocation;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Règlement ventilé : un versement solde plusieurs factures d'un même client,
 * chacune totalement ou partiellement.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);
});

/** @return array{0: \App\Models\User, 1: Customer, 2: Warehouse, 3: Product} */
function ventilationContexte(): array
{
    $lieu = Warehouse::factory()->create();
    $user = grantUser(
        ['sale.create', 'payment.create', 'payment.view'],
        ['warehouse_id' => $lieu->id],
    );
    $produit = Product::factory()->create([
        'cost_price' => 10,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $lieu->id,
        'product_id' => $produit->id,
        'quantity' => 500,
        'reserved_quantity' => 0,
        'average_cost' => '10.00',
    ]);

    return [$user, Customer::factory()->create(), $lieu, $produit];
}

/** Crée une facture confirmée du montant voulu, donc entièrement due. */
function factureDue(\App\Models\User $user, Customer $client, Warehouse $lieu, Product $produit, float $montant): int
{
    $id = test()->actingAs($user)->postJson('/api/v1/sales', [
        'type' => Sale::TYPE_INVOICE,
        'warehouse_id' => $lieu->id,
        'customer_id' => $client->id,
        'lines' => [['product_id' => $produit->id, 'quantity' => 1, 'unit_price' => $montant]],
    ])->assertCreated()->json('data.id');

    test()->actingAs($user)->postJson("/api/v1/sales/{$id}/confirm")->assertOk();

    return (int) $id;
}

it('solde deux factures d\'un seul versement', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 300);
    $b = factureDue($user, $client, $lieu, $produit, 200);

    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 500,
        'received_at' => now()->toDateString(),
        'allocations' => [
            ['sale_id' => $a, 'amount' => 300],
            ['sale_id' => $b, 'amount' => 200],
        ],
    ])->assertCreated();

    expect(Sale::query()->find($a)->payment_status)->toBe('paid')
        ->and(Sale::query()->find($b)->payment_status)->toBe('paid')
        ->and(PaymentAllocation::query()->count())->toBe(2);
});

it('regle partiellement une facture et solde l\'autre', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 300);
    $b = factureDue($user, $client, $lieu, $produit, 200);

    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 320,
        'received_at' => now()->toDateString(),
        'allocations' => [
            ['sale_id' => $a, 'amount' => 120],
            ['sale_id' => $b, 'amount' => 200],
        ],
    ])->assertCreated();

    $partielle = Sale::query()->find($a);
    expect($partielle->payment_status)->toBe('partial')
        ->and((float) $partielle->paid_amount)->toBe(120.0)
        ->and(Sale::query()->find($b)->payment_status)->toBe('paid');
});

it('refuse une repartition qui ne correspond pas au montant encaisse', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 300);

    // Sans ce contrôle, la différence disparaîtrait sans trace.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 500,
        'received_at' => now()->toDateString(),
        'allocations' => [['sale_id' => $a, 'amount' => 300]],
    ])->assertStatus(422);
});

it('refuse d\'affecter plus que le du d\'une facture', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 300);

    // Un trop-perçu rendrait la facture soldée et l'excédent invisible.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 400,
        'received_at' => now()->toDateString(),
        'allocations' => [['sale_id' => $a, 'amount' => 400]],
    ])->assertStatus(422);

    expect((float) Sale::query()->find($a)->paid_amount)->toBe(0.0);
});

it('refuse la facture d\'un autre client', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $autre = Customer::factory()->create();
    $facture = factureDue($user, $autre, $lieu, $produit, 100);

    // L'affecter réduirait l'encours du mauvais compte.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 100,
        'received_at' => now()->toDateString(),
        'allocations' => [['sale_id' => $facture, 'amount' => 100]],
    ])->assertStatus(422);
});

it('refuse deux fois la meme facture dans une repartition', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 300);

    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 200,
        'received_at' => now()->toDateString(),
        'allocations' => [
            ['sale_id' => $a, 'amount' => 100],
            ['sale_id' => $a, 'amount' => 100],
        ],
    ])->assertStatus(422);
});

it('liste les factures encore dues du client, des plus anciennes', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 300);
    $b = factureDue($user, $client, $lieu, $produit, 200);

    // Soldée : elle ne doit plus figurer parmi les factures dues.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 200,
        'received_at' => now()->toDateString(),
        'allocations' => [['sale_id' => $b, 'amount' => 200]],
    ])->assertCreated();

    $dues = test()->actingAs($user)
        ->getJson("/api/v1/customers/{$client->id}/open-invoices")
        ->assertOk()->json('data');

    // JSON rend 300 et non 300.0 : c'est la valeur qui compte, pas le type.
    expect($dues)->toHaveCount(1)
        ->and($dues[0]['id'])->toBe($a)
        ->and((float) $dues[0]['remaining'])->toBe(300.0);
});

it('reste compatible avec un reglement vise sur une seule facture', function (): void {
    [$user, $client, $lieu, $produit] = ventilationContexte();
    $a = factureDue($user, $client, $lieu, $produit, 150);

    // L'ancienne forme (sale_id) doit continuer de fonctionner et produire
    // désormais sa ventilation.
    test()->actingAs($user)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 150,
        'sale_id' => $a,
        'received_at' => now()->toDateString(),
    ])->assertCreated();

    expect(Sale::query()->find($a)->payment_status)->toBe('paid')
        ->and(PaymentAllocation::query()->where('sale_id', $a)->count())->toBe(1);
});
