<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use App\Domain\Catalog\Models\Product;
use App\Support\Concerns\BelongsToWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * État actuel du stock d'un article dans un lieu.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int $quantity
 * @property int $reserved_quantity
 * @property string $average_cost
 */
class Stock extends Model
{
    use BelongsToWarehouse;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'reserved_quantity',
        'average_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'average_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
