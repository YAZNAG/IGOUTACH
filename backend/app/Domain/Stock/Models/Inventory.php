<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Support\Scopes\WarehouseScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int $warehouse_id
 * @property Carbon|null $counted_at
 * @property string $status
 */
class Inventory extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference',
        'warehouse_id',
        'counted_at',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'note',
    ];

    /**
     * Cloisonnement par lieu : sans la permission « stock.view_global »,
     * un utilisateur ne voit que les inventaires de son propre lieu.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new WarehouseScope);
    }

    protected function casts(): array
    {
        return [
            'counted_at' => 'date',
            'approved_at' => 'datetime',
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
     * @return HasMany<InventoryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InventoryLine::class);
    }
}
