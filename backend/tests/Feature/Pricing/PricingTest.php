<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Actions\SetProductPricesAction;
use App\Domain\Pricing\Contracts\PriceResolverInterface;
use App\Domain\Pricing\DTOs\PriceLevelData;
use App\Domain\Pricing\Exceptions\InvalidPriceOrderException;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use Database\Seeders\PriceTypeSeeder;

beforeEach(function () {
    $this->seed(PriceTypeSeeder::class);
    $this->product = Product::factory()->create(['cost_price' => 10]);

    app(SetProductPricesAction::class)->execute($this->product->id, [
        new PriceLevelData(PriceType::DETAIL, 18),
        new PriceLevelData(PriceType::SEMI_GROS, 16),
        new PriceLevelData(PriceType::GROS, 14),
    ]);
});

it('résout le prix détail pour une petite quantité', function () {
    $resolved = app(PriceResolverInterface::class)->resolve($this->product->id, 1);
    expect($resolved->priceTypeCode)->toBe(PriceType::DETAIL)->and($resolved->amount)->toBe(18.0);
});

it('résout le demi-gros au palier de 10', function () {
    $resolved = app(PriceResolverInterface::class)->resolve($this->product->id, 10);
    expect($resolved->priceTypeCode)->toBe(PriceType::SEMI_GROS)->and($resolved->amount)->toBe(16.0);
});

it('résout le gros au palier de 50', function () {
    $resolved = app(PriceResolverInterface::class)->resolve($this->product->id, 60);
    expect($resolved->priceTypeCode)->toBe(PriceType::GROS)->and($resolved->amount)->toBe(14.0);
});

it('refuse un ordre de prix invalide (gros > détail)', function () {
    app(SetProductPricesAction::class)->execute($this->product->id, [
        new PriceLevelData(PriceType::DETAIL, 10),
        new PriceLevelData(PriceType::GROS, 20),
    ]);
})->throws(InvalidPriceOrderException::class);

it('historise : modifier un prix clôt la ligne précédente', function () {
    // Avance le temps pour un valid_from distinct du prix initial (append-only).
    $this->travelTo(now()->addMinute());

    app(SetProductPricesAction::class)->execute($this->product->id, [
        new PriceLevelData(PriceType::DETAIL, 20),
    ]);

    $detailId = PriceType::where('code', PriceType::DETAIL)->value('id');
    // 2 lignes détail : une clôturée, une en vigueur.
    $this->assertDatabaseCount('product_prices', 4); // 3 initiales + 1 nouvelle
    expect(
        ProductPrice::where('product_id', $this->product->id)
            ->where('price_type_id', $detailId)
            ->whereNull('valid_to')->value('amount')
    )->toBe('20.00');
});

it('expose les 3 niveaux avec marges via l\'API', function () {
    $user = grantUser(['price.view', 'product.view_cost_price']);

    $this->actingAs($user)
        ->getJson("/api/v1/products/{$this->product->id}/prices")
        ->assertOk()
        ->assertJsonPath('data.unit_cost', 10)
        ->assertJsonPath('data.levels.0.code', PriceType::DETAIL)
        ->assertJsonPath('data.levels.0.amount', 18);
});

it('refuse la gestion des tarifs sans price.manage', function () {
    $user = grantUser(['price.view']);

    $this->actingAs($user)
        ->putJson("/api/v1/products/{$this->product->id}/prices", [
            'prices' => [['price_type_code' => 'detail', 'amount' => 25]],
        ])
        ->assertForbidden();
});
