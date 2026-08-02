<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'number' => 'BC-'.now()->year.'-'.str_pad((string) $this->faker->unique()->randomNumber(4), 4, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'ordered_at' => $this->faker->optional()->dateTime(),
            'expected_at' => $this->faker->optional()->dateTime(),
            'status_id' => PurchaseOrderStatus::where('code', 'draft')->first()?->id ?? 1,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
