<?php

declare(strict_types=1);

namespace App\Domain\Sales\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Session de caisse : ouverture avec fonds, clôture avec écart.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $opened_by
 * @property Carbon $opened_at
 * @property string $opening_amount
 * @property Carbon|null $closed_at
 * @property string|null $closing_amount
 * @property string|null $expected_amount
 * @property string|null $difference
 * @property string $status
 */
final class CashSession extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'warehouse_id',
        'opened_by',
        'opened_at',
        'opening_amount',
        'closed_at',
        'closing_amount',
        'expected_amount',
        'difference',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
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
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
