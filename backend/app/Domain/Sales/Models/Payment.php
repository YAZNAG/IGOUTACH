<?php

declare(strict_types=1);

namespace App\Domain\Sales\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Settings\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Encaissement client (espèces, chèque, virement, carte).
 *
 * @property int $id
 * @property string $reference
 * @property int $customer_id
 * @property int|null $sale_id
 * @property int|null $payment_method_id
 * @property int|null $cash_session_id
 * @property string $amount
 * @property string|null $cheque_status
 * @property string|null $cheque_reference
 * @property Carbon $received_at
 * @property int|null $user_id
 * @property string|null $note
 */
final class Payment extends Model
{
    public const CHEQUE_RECEIVED = 'received';

    public const CHEQUE_DEPOSITED = 'deposited';

    public const CHEQUE_CLEARED = 'cleared';

    public const CHEQUE_BOUNCED = 'bounced';

    protected $fillable = [
        'reference',
        'customer_id',
        'sale_id',
        'payment_method_id',
        'cash_session_id',
        'amount',
        'cheque_status',
        'cheque_reference',
        'received_at',
        'user_id',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_at' => 'date',
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
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * @return BelongsTo<CashSession, $this>
     */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }
}
