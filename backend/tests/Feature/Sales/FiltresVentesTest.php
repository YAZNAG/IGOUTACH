<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Filtres de la liste des ventes : lieu, periode, famille d'articles.
 */
function venteDe(Product $produit, Warehouse $lieu, string $reference, ?string $quand = null): Sale
{
    $date = $quand !== null ? Carbon\Carbon::parse($quand) : now();

    $vente = Sale::query()->create([
        'reference' => $reference,
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => Customer::factory()->create()->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => 100,
        'discount_percent' => 0,
        'total' => 100,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => $date,
    ]);

    // « created_at » n'est pas assignable en masse : sans cette ecriture
    // explicite, une vente ancienne naitrait avec la date du jour et le test
    // de periode ne prouverait rien.
    $vente->forceFill(['created_at' => $date])->save();

    $vente->lines()->create([
        'product_id' => $produit->id,
        'quantity' => 1,
        'unit_price' => 100,
        'line_total' => 100,
    ]);

    return $vente;
}

it('filtre les ventes par famille d\'articles', function (): void {
    $user = grantUser(['sale.create', 'stock.view_global']);
    $lieu = Warehouse::factory()->create();

    $cables = Category::factory()->create(['name' => 'Cables']);
    $piles = Category::factory()->create(['name' => 'Piles']);

    venteDe(Product::factory()->create(['category_id' => $cables->id]), $lieu, 'VTE-CAB');
    venteDe(Product::factory()->create(['category_id' => $piles->id]), $lieu, 'VTE-PIL');

    $references = collect(
        test()->actingAs($user)
            ->getJson("/api/v1/sales?type=invoice&category_id={$cables->id}")
            ->assertOk()->json('data'),
    )->pluck('reference');

    expect($references)->toContain('VTE-CAB')
        ->and($references)->not->toContain('VTE-PIL');
});

it('retient une vente des qu\'une seule de ses lignes releve de la famille', function (): void {
    $user = grantUser(['sale.create', 'stock.view_global']);
    $lieu = Warehouse::factory()->create();

    $cables = Category::factory()->create(['name' => 'Cables']);
    $piles = Category::factory()->create(['name' => 'Piles']);

    // On cherche « les ventes ou il y a eu des cables », pas « les ventes de
    // cables uniquement » : une vente melangee doit apparaitre.
    $vente = venteDe(Product::factory()->create(['category_id' => $piles->id]), $lieu, 'VTE-MIX');
    $vente->lines()->create([
        'product_id' => Product::factory()->create(['category_id' => $cables->id])->id,
        'quantity' => 1,
        'unit_price' => 50,
        'line_total' => 50,
    ]);

    $references = collect(
        test()->actingAs($user)
            ->getJson("/api/v1/sales?type=invoice&category_id={$cables->id}")
            ->assertOk()->json('data'),
    )->pluck('reference');

    expect($references)->toContain('VTE-MIX');
});

it('filtre par lieu et par periode', function (): void {
    $user = grantUser(['sale.create', 'stock.view_global']);
    $ici = Warehouse::factory()->create();
    $ailleurs = Warehouse::factory()->create();
    $produit = Product::factory()->create();

    venteDe($produit, $ici, 'VTE-ICI', now()->toDateString());
    venteDe($produit, $ailleurs, 'VTE-AILLEURS', now()->toDateString());
    venteDe($produit, $ici, 'VTE-VIEILLE', now()->subMonths(3)->toDateString());

    $parLieu = collect(
        test()->actingAs($user)
            ->getJson("/api/v1/sales?type=invoice&warehouse_id={$ici->id}")
            ->assertOk()->json('data'),
    )->pluck('reference');

    expect($parLieu)->toContain('VTE-ICI')
        ->and($parLieu)->not->toContain('VTE-AILLEURS');

    $depuis = now()->subDays(7)->toDateString();
    $recentes = collect(
        test()->actingAs($user)
            ->getJson("/api/v1/sales?type=invoice&date_from={$depuis}")
            ->assertOk()->json('data'),
    )->pluck('reference');

    expect($recentes)->toContain('VTE-ICI')
        ->and($recentes)->not->toContain('VTE-VIEILLE');
});
