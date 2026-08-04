<?php

declare(strict_types=1);

namespace App\Domain\Customers\Models;

use App\Domain\Pricing\Models\PriceType;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_company
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $ice
 * @property int|null $price_type_id
 * @property int|null $seller_id
 * @property int|null $warehouse_id
 * @property float $credit_limit
 * @property float $balance
 * @property bool $is_blocked
 * @property string|null $notes
 * @property bool $is_active
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'is_company',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'ice',
        'price_type_id',
        'seller_id',
        'warehouse_id',
        'credit_limit',
        'balance',
        'is_blocked',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_company' => 'boolean',
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_blocked' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return CustomerFactory::new();
    }

    /**
     * Crédit disponible = plafond − encours.
     */
    public function availableCredit(): float
    {
        return round((float) $this->credit_limit - (float) $this->balance, 2);
    }

    /**
     * Type de prix appliqué par défaut (détail / demi-gros / gros).
     *
     * @return BelongsTo<PriceType, $this>
     */
    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class);
    }

    /**
     * Vendeur référent.
     *
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Lieu de rattachement.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Utilisateur ayant créé le client (portée de visibilité).
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
