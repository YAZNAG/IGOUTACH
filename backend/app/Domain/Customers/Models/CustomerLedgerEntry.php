<?php

declare(strict_types=1);

namespace App\Domain\Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Écriture du grand-livre client : maintient l'encours par événements
 * (jamais de mise à jour directe du solde sans écriture).
 *
 * @property int $id
 * @property int $customer_id
 * @property string $type
 * @property string $amount
 * @property string $balance_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $note
 * @property int|null $user_id
 */
final class CustomerLedgerEntry extends Model
{
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_RETURN = 'return';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'note',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
