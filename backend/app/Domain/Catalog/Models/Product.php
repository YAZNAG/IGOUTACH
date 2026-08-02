<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Purchasing\Models\Supplier;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property int $category_id
 * @property int|null $brand_id
 * @property int $unit_id
 * @property string $cost_price
 * @property string $sale_price
 * @property string $tax_rate
 * @property bool $is_serialized
 * @property int|null $min_stock
 * @property bool $is_active
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'cost_price',
        'sale_price',
        'tax_rate',
        'is_serialized',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<ProductSerial, $this>
     */
    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    /**
     * Fiche technique (attributs nom → valeur).
     *
     * @return HasMany<ProductAttribute, $this>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('position');
    }

    /**
     * Médias (image principale + secondaires).
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_main')->orderBy('position');
    }

    /**
     * Fournisseurs référencés pour cet article.
     *
     * @return BelongsToMany<Supplier, $this>
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['supplier_reference', 'last_price', 'lead_time_days'])
            ->withTimestamps();
    }
}
