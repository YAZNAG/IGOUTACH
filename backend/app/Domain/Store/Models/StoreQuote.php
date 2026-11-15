<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Demande de devis venue de la boutique : au-delà d'un certain palier, la
 * vente ne se conclut plus en libre-service. Le client demande, la boutique
 * chiffre, le client accepte — et la commande naît de cette acceptation.
 *
 * @property int $id
 * @property string $number
 * @property string $status
 * @property string $public_token
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string|null $company
 * @property string $city
 * @property string|null $address
 * @property string|null $note
 * @property string|null $reply
 * @property string|null $decline_reason
 * @property float $subtotal
 * @property float $shipping_fee
 * @property float $total
 * @property Carbon|null $valid_until
 * @property int|null $warehouse_id
 * @property int|null $sale_id
 * @property int|null $handled_by
 * @property Carbon|null $quoted_at
 * @property Carbon|null $answered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class StoreQuote extends Model
{
    /** Le client a demandé ; la boutique n'a pas encore répondu. */
    public const STATUS_REQUESTED = 'requested';

    /** La boutique a chiffré et envoyé : la balle est chez le client. */
    public const STATUS_QUOTED = 'quoted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_QUOTED,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'number', 'status', 'public_token', 'customer_name', 'customer_phone', 'customer_email',
        'company', 'city', 'address', 'note', 'reply', 'decline_reason',
        'subtotal', 'shipping_fee', 'total', 'valid_until',
        'warehouse_id', 'sale_id', 'handled_by', 'ip', 'quoted_at', 'answered_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'shipping_fee' => 'float',
            'total' => 'float',
            'valid_until' => 'date',
            'quoted_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * Un devis envoyé dont la date de validité est passée ne s'accepte plus.
     * L'état en base n'est pas réécrit par une simple lecture : c'est la
     * réponse du client ou le tableau de bord qui le constate.
     */
    public function estPerime(): bool
    {
        return $this->status === self::STATUS_QUOTED
            && $this->valid_until !== null
            && $this->valid_until->endOfDay()->isPast();
    }

    /** @return HasMany<StoreQuoteLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StoreQuoteLine::class, 'quote_id');
    }

    /** @return HasMany<StoreOrder, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'quote_id');
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
