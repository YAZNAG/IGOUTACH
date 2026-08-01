<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transfer_id
 * @property int $product_id
 * @property int $quantity_sent
 * @property int|null $quantity_received
 * @property string $unit_cost
 */
class TransferLine extends Model
{
    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity_sent',
        'quantity_received',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_sent' => 'integer',
            'quantity_received' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Transfer, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
