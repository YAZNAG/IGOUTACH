<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Models;

use Database\Factories\WarehouseTypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $allows_sales
 * @property bool $allows_purchase_receipt
 * @property bool $requires_transfer_approval
 */
class WarehouseType extends Model
{
    /** @use HasFactory<WarehouseTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'allows_sales',
        'allows_purchase_receipt',
        'requires_transfer_approval',
    ];

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return WarehouseTypeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'allows_sales' => 'boolean',
            'allows_purchase_receipt' => 'boolean',
            'requires_transfer_approval' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Warehouse, $this>
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
}
