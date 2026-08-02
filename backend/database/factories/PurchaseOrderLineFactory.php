<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderLine>
 */
class PurchaseOrderLineFactory extends Factory
{
    protected $model = PurchaseOrderLine::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'received_quantity' => 0,
            'position' => 0,
        ];
    }
}
