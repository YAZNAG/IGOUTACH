<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\PricingData;
use App\Domain\Catalog\DTOs\ProductData;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;

final class ProductRepository implements ProductRepositoryInterface
{
    public function create(ProductData $data): Product
    {
        $attributes = $data->toAttributes();

        // Unité par défaut si non fournie (saisie « infos fixes »).
        $attributes['unit_id'] ??= $this->defaultUnitId();

        return Product::create($attributes);
    }

    public function update(Product $product, ProductData $data): Product
    {
        $product->update($data->toAttributes());

        return $product->refresh();
    }

    public function updatePricing(Product $product, PricingData $data): Product
    {
        $product->update($data->toAttributes());

        return $product->refresh();
    }

    private function defaultUnitId(): int
    {
        return (int) (Unit::query()->where('code', 'pcs')->value('id')
            ?? Unit::query()->value('id')
            ?? Unit::create(['code' => 'pcs', 'name' => 'Pièce'])->id);
    }
}
