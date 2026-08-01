<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\ProductData;
use App\Domain\Catalog\Models\Product;

final class UpdateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function execute(Product $product, ProductData $data): Product
    {
        return $this->products->update($product, $data);
    }
}
