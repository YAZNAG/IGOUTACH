<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Chèque suivi en portefeuille.
 *
 * Un chèque reçu d'un client reste disponible tant qu'il est en portefeuille :
 * il peut ensuite être endossé au profit d'un fournisseur ou encaissé.
 */
final class Cheque extends Model
{
    use HasFactory;

    public const INSTRUMENT_CHEQUE = 'cheque';

    /** Lettre de change : memes attributs et meme cycle qu'un cheque. */
    public const INSTRUMENT_TRAITE = 'traite';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    public const ORIGIN_CUSTOMER = 'customer';
    public const ORIGIN_OWN = 'own';
    public const ORIGIN_THIRD_PARTY = 'third_party';

    public const STATUS_PORTFOLIO = 'portfolio';
    public const STATUS_HANDED_OVER = 'handed_over';
    public const STATUS_CASHED = 'cashed';
    public const STATUS_BOUNCED = 'bounced';

    protected $fillable = [
        'instrument',
        'number',
        'cheque_date',
        'amount',
        'bank',
        'direction',
        'origin',
        'drawer_name',
        'customer_id',
        'supplier_id',
        'image_path',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Seuls les chèques reçus et encore en portefeuille peuvent être endossés :
     * un chèque déjà remis ou encaissé ne doit pas pouvoir servir deux fois.
     */
    public function estEndossable(): bool
    {
        return $this->direction === self::DIRECTION_IN
            && $this->status === self::STATUS_PORTFOLIO;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path !== null
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    /** Nom effectivement porté sur le chèque. */
    public function getSignataireAttribute(): string
    {
        if ($this->drawer_name !== null && $this->drawer_name !== '') {
            return $this->drawer_name;
        }

        return match ($this->origin) {
            self::ORIGIN_OWN => 'Notre société',
            self::ORIGIN_CUSTOMER => $this->customer?->name ?? '—',
            default => '—',
        };
    }
}
