<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\PricingData;
use App\Domain\Catalog\Models\Product;

final class SetProductPriceAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function execute(Product $product, PricingData $data): Product
    {
        return $this->products->updatePricing($product, $data);
    }
}
