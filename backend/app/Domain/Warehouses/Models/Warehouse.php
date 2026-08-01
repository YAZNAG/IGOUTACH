<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Models;

use App\Domain\Stock\Models\Stock;
use App\Models\User;
use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $warehouse_type_id
 * @property int|null $manager_id
 * @property int|null $parent_id
 * @property bool $is_active
 */
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return WarehouseFactory::new();
    }

    protected $fillable = [
        'code',
        'name',
        'warehouse_type_id',
        'manager_id',
        'parent_id',
        'address',
        'city',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WarehouseType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'warehouse_type_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Dépôt de rattachement (pour un véhicule).
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Warehouse, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Utilisateurs rattachés à ce lieu.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'warehouse_id');
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function isVehicle(): bool
    {
        return $this->type()->where('code', 'vehicle')->exists();
    }
}
