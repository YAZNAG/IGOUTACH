<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Actions;

use App\Domain\Warehouses\Exceptions\WarehouseInUseException;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Active / désactive un lieu. Désactivation refusée si le stock n'est pas à zéro.
 */
final class ToggleWarehouseAction
{
    public function execute(Warehouse $warehouse): Warehouse
    {
        if ($warehouse->is_active) {
            // Requête directe (hors Global Scope d'isolation) : on doit voir le
            // stock du lieu ciblé quel que soit le lieu de l'utilisateur courant.
            $stock = DB::table('stocks')->where('warehouse_id', $warehouse->id)->where('quantity', '>', 0);
            $references = (clone $stock)->count();

            if ($references > 0) {
                $value = (float) (clone $stock)->sum(DB::raw('quantity * average_cost'));
                throw WarehouseInUseException::stockNotEmpty($references, $value);
            }
        }

        $warehouse->update(['is_active' => ! $warehouse->is_active]);

        return $warehouse->refresh();
    }
}
