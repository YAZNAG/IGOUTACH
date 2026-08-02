<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ligne de bon de réception (quantité reçue + prix d'achat réel).
 *
 * @property int $id
 * @property int $goods_receipt_id
 * @property int $product_id
 * @property int|null $purchase_order_line_id
 * @property int $quantity
 * @property string $unit_price
 * @property string|null $over_receipt_reason
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class GoodsReceiptLine extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'product_id',
        'purchase_order_line_id',
        'quantity',
        'unit_price',
        'over_receipt_reason',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<PurchaseOrderLine, $this>
     */
    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    /**
     * Total de la ligne (quantité × prix unitaire).
     */
    public function lineTotal(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }
}
