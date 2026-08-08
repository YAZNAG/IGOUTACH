<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use App\Support\Scopes\WarehouseScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Charge (dépense) par lieu et par utilisateur, validée par le responsable.
 *
 * @property int $id
 * @property int $expense_category_id
 * @property int|null $warehouse_id
 * @property int $user_id
 * @property string $label
 * @property string $amount
 * @property Carbon $expense_date
 * @property string|null $receipt_path
 * @property string $status
 * @property int|null $approved_by
 */
final class Expense extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'expense_category_id',
        'warehouse_id',
        'user_id',
        'label',
        'amount',
        'payment_method_id',
        'expense_date',
        'receipt_path',
        'status',
        'approved_by',
    ];

    /**
     * Cloisonnement par lieu : sans la permission « stock.view_global »,
     * un utilisateur ne voit que la charge de son propre lieu.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new WarehouseScope);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Settings\Models\PaymentMethod::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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
}
