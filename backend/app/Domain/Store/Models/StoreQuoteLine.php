<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'une demande de devis. Le prix est d'abord indicatif — celui du
 * palier atteint par la quantité — puis remplacé par le prix ferme que la
 * boutique accorde.
 *
 * @property int $id
 * @property int $quote_id
 * @property int $product_id
 * @property string $name
 * @property string|null $sku
 * @property int $quantity
 * @property float $unit_price
 * @property string|null $price_type_code
 * @property float $line_total
 * @property int|null $stock_at_request
 */
final class StoreQuoteLine extends Model
{
    protected $fillable = [
        'quote_id', 'product_id', 'name', 'sku', 'quantity',
        'unit_price', 'price_type_code', 'line_total', 'stock_at_request',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'float',
            'line_total' => 'float',
            'stock_at_request' => 'integer',
        ];
    }

    /** @return BelongsTo<StoreQuote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(StoreQuote::class, 'quote_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
