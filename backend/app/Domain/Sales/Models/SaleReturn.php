<?php

declare(strict_types=1);

namespace App\Domain\Sales\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un retour client rattaché à la ligne de vente d'origine.
 *
 * @property int $id
 * @property int $sale_id
 * @property int $sale_line_id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $customer_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $amount
 * @property float $credited_amount
 * @property string $condition
 * @property string|null $note
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property int|null $created_by
 */
final class SaleReturn extends Model
{
    public const CONDITION_RESELLABLE = 'resellable';

    public const CONDITION_DEFECTIVE = 'defective';

    protected $fillable = [
        'sale_id',
        'sale_line_id',
        'product_id',
        'warehouse_id',
        'customer_id',
        'quantity',
        'unit_price',
        'amount',
        'credited_amount',
        'condition',
        'note',
        'occurred_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'credited_amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class, 'sale_line_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
