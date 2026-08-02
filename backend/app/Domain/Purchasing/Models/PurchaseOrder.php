<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bon de commande fournisseur.
 *
 * @property int $id
 * @property string $reference
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $status
 * @property Carbon|null $expected_at
 * @property int|null $created_by
 * @property string|null $note
 */
final class PurchaseOrder extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference',
        'supplier_id',
        'warehouse_id',
        'status',
        'expected_at',
        'created_by',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['expected_at' => 'date'];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }
}
