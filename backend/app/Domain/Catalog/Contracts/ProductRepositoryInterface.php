<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\DTOs\PricingData;
use App\Domain\Catalog\DTOs\ProductData;
use App\Domain\Catalog\Models\Product;

interface ProductRepositoryInterface
{
    public function create(ProductData $data): Product;

    public function update(Product $product, ProductData $data): Product;

    public function updatePricing(Product $product, PricingData $data): Product;
}
