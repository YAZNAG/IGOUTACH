<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\UnitInUseException;
use App\Domain\Catalog\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Crée ou met à jour une unité.
 */
final class SaveUnitAction
{
    /**
     * @param  array{code: string, name: string, is_decimal: bool, position?: int, is_active?: bool}  $data
     */
    public function create(array $data): Unit
    {
        return Unit::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_decimal' => $data['is_decimal'],
            'position' => $data['position'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array{code: string, name: string, is_decimal: bool, position?: int, is_active?: bool}  $data
     */
    public function update(Unit $unit, array $data): Unit
    {
        // Repasser une unité en non décimale est refusé si des mouvements
        // de stock à quantité décimale existent déjà pour ses articles.
        if ($unit->is_decimal && $data['is_decimal'] === false && $this->hasDecimalMovements($unit)) {
            throw UnitInUseException::decimalMovementsExist();
        }

        $unit->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_decimal' => $data['is_decimal'],
            'position' => $data['position'] ?? $unit->position,
            'is_active' => $data['is_active'] ?? $unit->is_active,
        ]);

        return $unit->refresh();
    }

    private function hasDecimalMovements(Unit $unit): bool
    {
        return DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('products.unit_id', $unit->id)
            ->whereRaw('stock_movements.quantity <> ROUND(stock_movements.quantity)')
            ->exists();
    }
}
