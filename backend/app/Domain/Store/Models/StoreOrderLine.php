<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $name
 * @property string|null $sku
 * @property int $quantity
 * @property float $unit_price
 * @property float $line_total
 */
final class StoreOrderLine extends Model
{
    protected $fillable = ['order_id', 'product_id', 'name', 'sku', 'quantity', 'unit_price', 'line_total'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price' => 'float', 'line_total' => 'float'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
