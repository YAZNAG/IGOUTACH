<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-#####')),
            'barcode' => $this->faker->unique()->ean13(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'cost_price' => $this->faker->randomFloat(2, 10, 500),
            'sale_price' => $this->faker->randomFloat(2, 500, 1500),
            'tax_rate' => 0,
            'is_serialized' => false,
            'min_stock' => 5,
            'is_active' => true,
        ];
    }
}
