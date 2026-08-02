<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use Database\Seeders\PriceTypeSeeder;

beforeEach(function () {
    $this->seed(PriceTypeSeeder::class);
});

it('prévisualise les tarifs calculés par marge sur le prix d\'achat', function () {
    $product = Product::factory()->create(['cost_price' => 100]);
    $user = grantUser(['price.bulk_update']);

    $response = $this->actingAs($user)->postJson('/api/v1/prices/bulk-margin', [
        'margins' => ['detail' => 30, 'semi_gros' => 20, 'gros' => 10],
    ])->assertOk();

    $row = collect($response->json('data.rows'))->firstWhere('product_id', $product->id);
    expect($row)->not->toBeNull()
        ->and((float) $row['levels']['detail']['next'])->toBe(130.0)
        ->and((float) $row['levels']['semi_gros']['next'])->toBe(120.0)
        ->and((float) $row['levels']['gros']['next'])->toBe(110.0)
        ->and($response->json('data.applied'))->toBeFalse();

    // Prévisualisation seule : aucun prix créé.
    expect(ProductPrice::where('product_id', $product->id)->count())->toBe(0);
});

it('applique les marges et crée les trois niveaux de prix', function () {
    $product = Product::factory()->create(['cost_price' => 50]);
    $user = grantUser(['price.bulk_update']);

    $this->actingAs($user)->postJson('/api/v1/prices/bulk-margin', [
        'margins' => ['detail' => 40, 'semi_gros' => 25, 'gros' => 15],
        'apply' => true,
    ])->assertOk()->assertJsonPath('data.applied', true);

    $amount = function (string $code) use ($product): ?string {
        $typeId = PriceType::where('code', $code)->value('id');

        return ProductPrice::where('product_id', $product->id)
            ->where('price_type_id', $typeId)
            ->whereNull('valid_to')
            ->value('amount');
    };

    expect($amount(PriceType::DETAIL))->toBe('70.00')
        ->and($amount(PriceType::SEMI_GROS))->toBe('62.50')
        ->and($amount(PriceType::GROS))->toBe('57.50');
});

it('ignore les articles sans prix d\'achat', function () {
    Product::factory()->create(['cost_price' => 0]);
    $user = grantUser(['price.bulk_update']);

    $response = $this->actingAs($user)->postJson('/api/v1/prices/bulk-margin', [
        'margins' => ['detail' => 30],
    ])->assertOk();

    expect($response->json('data.skipped'))->toBeGreaterThanOrEqual(1);
});

it('refuse une requête sans aucune marge', function () {
    $user = grantUser(['price.bulk_update']);

    $this->actingAs($user)->postJson('/api/v1/prices/bulk-margin', [
        'margins' => [],
    ])->assertStatus(422);
});

it('refuse sans la permission price.bulk_update', function () {
    $user = grantUser(['price.view']);

    $this->actingAs($user)->postJson('/api/v1/prices/bulk-margin', [
        'margins' => ['detail' => 30],
    ])->assertForbidden();
});
