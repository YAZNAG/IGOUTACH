<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Catalog\Models\Product;
use Database\Factories\PurchaseOrderLineFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ligne de bon de commande (reliquat = quantity − received_quantity).
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int $quantity
 * @property int $received_quantity
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PurchaseOrderLine extends Model
{
    /** @use HasFactory<PurchaseOrderLineFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'received_quantity' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Quantité restante à recevoir.
     */
    public function remaining(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return PurchaseOrderLineFactory::new();
    }
}
