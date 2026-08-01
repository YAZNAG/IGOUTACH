<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $serial_number
 * @property int|null $warehouse_id
 * @property bool $is_sold
 */
class ProductSerial extends Model
{
    protected $fillable = [
        'product_id',
        'serial_number',
        'warehouse_id',
        'is_sold',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'is_sold' => 'boolean',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
