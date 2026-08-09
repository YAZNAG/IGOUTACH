<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property int $transfer_status_id
 * @property Carbon|null $sent_at
 * @property Carbon|null $received_at
 * @property string|null $note
 */
class Transfer extends Model
{
    protected $fillable = [
        'reference',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_status_id',
        'created_by',
        'requested_by',
        'approved_by',
        'received_by',
        'sent_at',
        'requested_at',
        'approved_at',
        'received_at',
        'note',
        'refusal_reason',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * @return BelongsTo<TransferStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TransferStatus::class, 'transfer_status_id');
    }

    /**
     * @return HasMany<TransferLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TransferLine::class);
    }
}
