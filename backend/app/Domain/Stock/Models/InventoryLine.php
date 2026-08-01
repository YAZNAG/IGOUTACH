<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_id
 * @property int $product_id
 * @property int $counted_quantity
 * @property int $system_quantity
 * @property int $difference
 */
class InventoryLine extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_id',
        'counted_quantity',
        'system_quantity',
        'difference',
    ];

    protected function casts(): array
    {
        return [
            'counted_quantity' => 'integer',
            'system_quantity' => 'integer',
            'difference' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
