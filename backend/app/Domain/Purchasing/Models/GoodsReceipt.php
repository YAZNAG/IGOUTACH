<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bon de réception fournisseur (BR-YYYY-0001).
 *
 * @property int $id
 * @property string $number
 * @property int|null $purchase_order_id
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property Carbon $received_at
 * @property string|null $invoice_number
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class GoodsReceipt extends Model
{
    protected $fillable = [
        'number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'received_at',
        'invoice_number',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<GoodsReceiptLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    /**
     * Filtrer par numéro.
     *
     * @param  Builder<self>  $query
     */
    public function scopeByNumber(Builder $query, string $number): Builder
    {
        return $query->where('number', $number);
    }

    /**
     * Filtrer par fournisseur.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }
}
