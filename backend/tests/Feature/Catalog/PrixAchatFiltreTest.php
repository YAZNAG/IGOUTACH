<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Page « Prix d'achat » : filtre sur les articles sans prix, tri par colonne.
 */
function articlePrix(string $sku, float $cout): Product
{
    return Product::factory()->create([
        'sku' => $sku,
        'name' => "Article $sku",
        'cost_price' => $cout,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function receptionPour(Product $p, float $prix): void
{
    $lieu = Warehouse::factory()->create();
    $bon = GoodsReceipt::withoutGlobalScopes()->create([
        'number' => 'BR-'.$p->sku,
        'supplier_id' => Supplier::factory()->create()->id,
        'warehouse_id' => $lieu->id,
        'received_at' => now(),
    ]);
    DB::table('goods_receipt_lines')->insert([
        'goods_receipt_id' => $bon->id,
        'product_id' => $p->id,
        'quantity' => 1,
        'unit_price' => $prix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('isole les articles sans aucun prix d\'achat', function (): void {
    $user = grantUser(['product.view_cost_price']);

    articlePrix('SANS-A', 0);
    articlePrix('SANS-B', 0);
    articlePrix('FICHE', 12.5);
    $recu = articlePrix('RECU', 0);
    receptionPour($recu, 9);

    $skus = fn (string $filtre) => collect(
        $this->actingAs($user)->getJson('/api/v1/product-costs?per_page=100&prix_achat='.$filtre)->assertOk()->json('data'),
    )->pluck('sku')->sort()->values()->all();

    expect($skus('sans'))->toBe(['SANS-A', 'SANS-B'])
        ->and($skus('sans_bon'))->toBe(['FICHE', 'SANS-A', 'SANS-B'])
        ->and($skus('avec'))->toBe(['FICHE', 'RECU']);
});

it('trie sur le prix d\'achat retenu et sur le tarif détail', function (): void {
    $user = grantUser(['product.view_cost_price']);

    articlePrix('P-30', 30);
    $p = articlePrix('P-BON', 5);
    receptionPour($p, 50);
    articlePrix('P-10', 10);

    $parPrix = collect(
        $this->actingAs($user)->getJson('/api/v1/product-costs?per_page=100&sort=purchase_price&direction=desc')->assertOk()->json('data'),
    )->pluck('sku')->values()->all();

    // Le bon (50) prime sur la fiche (5) : P-BON passe devant P-30.
    expect($parPrix)->toBe(['P-BON', 'P-30', 'P-10']);

    $this->actingAs($user)->getJson('/api/v1/product-costs?per_page=100&sort=detail_price&direction=asc')->assertOk();
});
