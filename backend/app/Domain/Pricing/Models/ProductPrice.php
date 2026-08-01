<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Prix d'un article pour un niveau, historisé (append-only).
 *
 * @property int $id
 * @property int $product_id
 * @property int $price_type_id
 * @property string $amount
 * @property int|null $min_quantity
 * @property string $min_margin_percent
 * @property Carbon $valid_from
 * @property Carbon|null $valid_to
 */
class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'price_type_id',
        'amount',
        'min_quantity',
        'min_margin_percent',
        'valid_from',
        'valid_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'min_quantity' => 'integer',
            'min_margin_percent' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
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
     * @return BelongsTo<PriceType, $this>
     */
    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class);
    }
}
