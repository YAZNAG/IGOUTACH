<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use App\Domain\Catalog\Models\Product;
use App\Support\Concerns\BelongsToWarehouse;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique immuable des mouvements de stock (append-only).
 * Aucun UPDATE ni DELETE : une correction se fait par un mouvement inverse.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int $movement_type_id
 * @property int $quantity
 * @property string $unit_cost
 * @property int $balance_after
 */
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    use BelongsToWarehouse;

    public const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_type_id',
        'quantity',
        'unit_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'user_id',
        'note',
        // Date du document (réception, inventaire…) : si fournie, elle remplace
        // l'horodatage courant comme date du mouvement.
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MovementType, $this>
     */
    public function movementType(): BelongsTo
    {
        return $this->belongsTo(MovementType::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return StockMovementFactory::new();
    }
}
