<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Settings\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Règlement d'un crédit fournisseur (total ou partiel) après réception.
 *
 * @property int $id
 * @property int $goods_receipt_id
 * @property int $supplier_id
 * @property int|null $payment_method_id
 * @property string $amount
 * @property Carbon $paid_at
 * @property string|null $notes
 * @property int|null $created_by
 */
final class SupplierPayment extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'supplier_id',
        'payment_method_id',
        'cheque_id',
        'amount',
        'paid_at',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
