<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;

/**
 * Coût unitaire de référence pour le calcul des marges : CMUP pondéré sur tous
 * les lieux, à défaut le dernier prix d'achat de la fiche article.
 */
final class ProductCostResolver
{
    public function unitCost(Product $product): float
    {
        /** @var object{q: int|null, v: float|null}|null $row */
        $row = Stock::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->selectRaw('SUM(quantity) as q, SUM(quantity * average_cost) as v')
            ->first();

        $quantity = (int) ($row->q ?? 0);
        $value = (float) ($row->v ?? 0);

        if ($quantity > 0) {
            return round($value / $quantity, 2);
        }

        return (float) $product->cost_price;
    }
}
