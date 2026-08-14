<?php

declare(strict_types=1);

namespace App\Domain\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Part d'un règlement affectée à une facture.
 *
 * Un versement solde souvent plusieurs factures à la fois, parfois
 * partiellement : cette table dit combien va sur chacune, là où la seule
 * colonne `payments.sale_id` ne pouvait en désigner qu'une.
 *
 * @property int $id
 * @property int $payment_id
 * @property int $sale_id
 * @property string $amount
 */
class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'sale_id',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
