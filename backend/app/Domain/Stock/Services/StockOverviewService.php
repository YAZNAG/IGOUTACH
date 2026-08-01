<?php

declare(strict_types=1);

namespace App\Domain\Stock\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Lectures agrégées pour la vue globale (direction). Consultation seule.
 */
final class StockOverviewService
{
    /**
     * Indicateurs consolidés, tous lieux confondus.
     *
     * @return array{warehouses: int, products: int, total_units: int, distinct_in_stock: int}
     */
    public function summary(): array
    {
        return [
            'warehouses' => Warehouse::query()->where('is_active', true)->count(),
            'products' => Product::query()->where('is_active', true)->count(),
            'total_units' => (int) Stock::withoutGlobalScopes()->sum('quantity'),
            'distinct_in_stock' => Stock::withoutGlobalScopes()->where('quantity', '>', 0)
                ->distinct('product_id')->count('product_id'),
        ];
    }

    /**
     * Stock consolidé par article : SUM(quantity) GROUP BY product_id.
     *
     * @return list<array{product_id: int, sku: string, name: string, total_quantity: int}>
     */
    public function consolidatedStock(int $limit = 100): array
    {
        $rows = Stock::withoutGlobalScopes()
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->groupBy('product_id')
            ->having('total_quantity', '>', 0)
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        $products = Product::query()
            ->whereIn('id', $rows->pluck('product_id'))
            ->get(['id', 'sku', 'name'])
            ->keyBy('id');

        $result = [];

        foreach ($rows as $row) {
            $product = $products->get($row->product_id);

            if ($product === null) {
                continue;
            }

            $result[] = [
                'product_id' => (int) $row->product_id,
                'sku' => $product->sku,
                'name' => $product->name,
                'total_quantity' => (int) $row->getAttribute('total_quantity'),
            ];
        }

        return $result;
    }
}
