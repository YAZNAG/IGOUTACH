<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Domain\Warehouses\Models\Warehouse;
use App\Support\Scopes\WarehouseScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * À utiliser sur tout modèle isolé par lieu (Stock, StockMovement, Sale,
 * Expense, Inventory…). Applique le WarehouseScope et expose la relation.
 */
trait BelongsToWarehouse
{
    public static function bootBelongsToWarehouse(): void
    {
        static::addGlobalScope(new WarehouseScope);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Permission qui débloque la vue inter-lieux pour ce modèle.
     * Surchargeable par modèle si besoin.
     */
    public function globalViewPermission(): string
    {
        return WarehouseScope::DEFAULT_GLOBAL_PERMISSION;
    }
}
