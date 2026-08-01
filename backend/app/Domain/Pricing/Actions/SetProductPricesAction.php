<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Contracts\ProductPriceRepositoryInterface;
use App\Domain\Pricing\DTOs\PriceLevelData;
use App\Domain\Pricing\Exceptions\InvalidPriceOrderException;
use App\Domain\Pricing\Models\PriceType;

final class SetProductPricesAction
{
    public function __construct(
        private readonly ProductPriceRepositoryInterface $prices,
    ) {}

    /**
     * @param  list<PriceLevelData>  $levels
     */
    public function execute(int $productId, array $levels, ?int $userId = null): void
    {
        $this->assertOrder($levels);

        $this->prices->replace($productId, $levels, $userId);

        // Dénormalisation pratique : products.sale_price reflète le prix détail.
        $detail = $this->amountFor($levels, PriceType::DETAIL);
        if ($detail !== null) {
            Product::query()->whereKey($productId)->update(['sale_price' => $detail]);
        }
    }

    /**
     * Contrôle : gros ≤ demi-gros ≤ détail.
     *
     * @param  list<PriceLevelData>  $levels
     */
    private function assertOrder(array $levels): void
    {
        $detail = $this->amountFor($levels, PriceType::DETAIL);
        $semi = $this->amountFor($levels, PriceType::SEMI_GROS);
        $gros = $this->amountFor($levels, PriceType::GROS);

        if ($semi !== null && $detail !== null && $semi > $detail) {
            throw InvalidPriceOrderException::make();
        }

        if ($gros !== null && $semi !== null && $gros > $semi) {
            throw InvalidPriceOrderException::make();
        }

        if ($gros !== null && $detail !== null && $gros > $detail) {
            throw InvalidPriceOrderException::make();
        }
    }

    /**
     * @param  list<PriceLevelData>  $levels
     */
    private function amountFor(array $levels, string $code): ?float
    {
        foreach ($levels as $level) {
            if ($level->priceTypeCode === $code) {
                return $level->amount;
            }
        }

        return null;
    }
}
