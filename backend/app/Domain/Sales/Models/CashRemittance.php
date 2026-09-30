<?php

declare(strict_types=1);

namespace App\Domain\Sales\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use App\Support\Scopes\WarehouseScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Remise de caisse : somme confiée par un lieu à l'administration.
 *
 * Deux temps, deux signatures. Le responsable déclare ce qu'il remet, la
 * direction confirme ce qu'elle a reçu. L'écart éventuel se voit alors tout
 * de suite, au lieu de se découvrir en fin de mois.
 *
 * @property int $id
 * @property string $reference
 * @property int $warehouse_id
 * @property int|null $cash_session_id
 * @property string $amount
 * @property string $status
 */
final class CashRemittance extends Model
{
    /** Déclarée par le lieu, pas encore confirmée par l'administration. */
    public const STATUS_PENDING = 'pending';

    /** Confirmée reçue : la somme est sortie du lieu pour de bon. */
    public const STATUS_RECEIVED = 'received';

    /**
     * Refusée : la direction n'a pas reçu cette somme.
     *
     * L'argent revient au solde du lieu, mais la ligne demeure : c'est la
     * trace du désaccord, et elle doit survivre à sa résolution.
     */
    public const STATUS_REFUSED = 'refused';

    protected $fillable = [
        'reference',
        'warehouse_id',
        'cash_session_id',
        'amount',
        'remitted_at',
        'status',
        'note',
        'proof_path',
        'created_by',
        'received_by',
        'received_at',
        'refused_by',
        'refused_at',
        'refusal_reason',
    ];

    /**
     * Cloisonnement par lieu : un responsable ne voit que ses propres remises.
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
            'remitted_at' => 'date',
            'received_at' => 'datetime',
            'refused_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function refuser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refused_by');
    }
}
