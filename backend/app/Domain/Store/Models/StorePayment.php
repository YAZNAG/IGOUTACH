<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Règlement reçu pour une commande en ligne (espèces à la livraison,
 * virement…), saisi par qui gère la boutique.
 *
 * @property int $id
 * @property int $order_id
 * @property string $method
 * @property float $amount
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon $received_at
 * @property string|null $note
 * @property int|null $received_by
 */
final class StorePayment extends Model
{
    protected $fillable = ['order_id', 'method', 'amount', 'reference', 'received_at', 'note', 'received_by'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'float', 'received_at' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
