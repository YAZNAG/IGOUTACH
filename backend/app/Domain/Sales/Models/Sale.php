<?php

declare(strict_types=1);

namespace App\Domain\Sales\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Document de vente : devis (sans effet stock) ou facture
 * (sortie de stock + créance client à la confirmation).
 *
 * @property int $id
 * @property string $reference
 * @property string $type
 * @property string $status
 * @property int $customer_id
 * @property int $warehouse_id
 * @property int|null $user_id
 * @property string $subtotal
 * @property string $discount_percent
 * @property string $total
 * @property string $paid_amount
 * @property string $payment_status
 * @property Carbon|null $confirmed_at
 * @property string|null $note
 */
final class Sale extends Model
{
    public const TYPE_QUOTE = 'quote';

    public const TYPE_INVOICE = 'invoice';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference',
        'type',
        'status',
        'customer_id',
        'quote_id',
        'warehouse_id',
        'user_id',
        'subtotal',
        'discount_percent',
        'total',
        'paid_amount',
        'payment_status',
        'confirmed_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SaleLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }
}
