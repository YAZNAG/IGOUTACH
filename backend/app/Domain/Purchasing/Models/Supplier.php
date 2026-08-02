<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Catalog\Models\Product;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $ice
 * @property string|null $rc
 * @property int $payment_terms_days
 * @property string|null $notes
 * @property bool $is_active
 */
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<SupplierContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    /**
     * Articles référencés chez ce fournisseur.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot(['supplier_reference', 'last_price', 'lead_time_days'])
            ->withTimestamps();
    }

    protected $fillable = [
        'code',
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'ice',
        'rc',
        'payment_terms_days',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return SupplierFactory::new();
    }
}
