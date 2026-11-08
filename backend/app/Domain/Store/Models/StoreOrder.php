<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Commande passée sur la boutique en ligne.
 *
 * @property int $id
 * @property string $number
 * @property string $status
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string $city
 * @property string $address
 * @property string|null $note
 * @property string $payment_method
 * @property string $payment_status
 * @property float $subtotal
 * @property float $shipping_fee
 * @property float $total
 * @property float $paid_amount
 * @property int|null $warehouse_id
 * @property int|null $sale_id
 * @property int|null $handled_by
 * @property string|null $cancel_reason
 */
final class StoreOrder extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_CONFIRMED, self::STATUS_PREPARING, self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED];

    public const PAYMENT_COD = 'cod';

    public const PAYMENT_TRANSFER = 'transfer';

    protected $fillable = [
        'number', 'status', 'customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'note',
        'payment_method', 'payment_status', 'subtotal', 'shipping_fee', 'total', 'paid_amount',
        'warehouse_id', 'sale_id', 'handled_by', 'cancel_reason', 'ip',
        'confirmed_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'float', 'shipping_fee' => 'float', 'total' => 'float', 'paid_amount' => 'float',
            'confirmed_at' => 'datetime', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    /** @return HasMany<StoreOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StoreOrderLine::class, 'order_id');
    }

    /** @return HasMany<StorePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(StorePayment::class, 'order_id')->orderBy('id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
