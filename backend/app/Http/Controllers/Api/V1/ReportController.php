<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Rapports du pilotage : ventes par période/lieu/vendeur, valorisation du
 * stock par lieu, top ventes, articles dormants et marges réalisées.
 */
final class ReportController extends Controller
{
    /**
     * Ventes agrégées sur une période, groupées par lieu, vendeur ou article.
     */
    public function sales(Request $request): JsonResponse|BinaryFileResponse
    {
        /** @var array{from?: string|null, to?: string|null, group?: string|null, format?: string|null} $data */
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'group' => ['nullable', 'in:warehouse,seller,product'],
            'format' => ['nullable', 'in:json,xlsx'],
        ]);

        $from = $data['from'] ?? now()->subDays(30)->format('Y-m-d');
        $to = $data['to'] ?? now()->format('Y-m-d');
        $group = $data['group'] ?? 'warehouse';

        $base = DB::table('sales')
            ->where('sales.type', 'invoice')
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.confirmed_at', [$from.' 00:00:00', $to.' 23:59:59']);

        $rows = match ($group) {
            'seller' => (clone $base)
                ->leftJoin('users', 'users.id', '=', 'sales.user_id')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc(DB::raw('SUM(sales.total)'))
                ->get([
                    DB::raw("COALESCE(users.name, '—') as label"),
                    DB::raw('COUNT(*) as documents'),
                    DB::raw('ROUND(SUM(sales.total), 2) as revenue'),
                    DB::raw('ROUND(SUM(sales.paid_amount), 2) as collected'),
                ]),
            'product' => DB::table('sale_lines')
                ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
                ->join('products', 'products.id', '=', 'sale_lines.product_id')
                ->where('sales.type', 'invoice')
                ->where('sales.status', 'confirmed')
                ->whereBetween('sales.confirmed_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->groupBy('products.id', 'products.sku', 'products.name')
                ->orderByDesc(DB::raw('SUM(sale_lines.line_total)'))
                ->limit(100)
                ->get([
                    DB::raw("CONCAT(products.sku, ' — ', products.name) as label"),
                    DB::raw('SUM(sale_lines.quantity) as documents'),
                    DB::raw('ROUND(SUM(sale_lines.line_total), 2) as revenue'),
                    DB::raw('ROUND(SUM(sale_lines.quantity * products.cost_price), 2) as collected'),
                ]),
            default => (clone $base)
                ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
                ->groupBy('warehouses.id', 'warehouses.code')
                ->orderByDesc(DB::raw('SUM(sales.total)'))
                ->get([
                    'warehouses.code as label',
                    DB::raw('COUNT(*) as documents'),
                    DB::raw('ROUND(SUM(sales.total), 2) as revenue'),
                    DB::raw('ROUND(SUM(sales.paid_amount), 2) as collected'),
                ]),
        };

        if (($data['format'] ?? 'json') === 'xlsx') {
            $headings = $group === 'product'
                ? ['Article', 'Quantité vendue', 'Chiffre d\'affaires', 'Coût (CMUP)']
                : [ucfirst($group === 'seller' ? 'Vendeur' : 'Lieu'), 'Documents', 'Chiffre d\'affaires', 'Encaissé'];

            $export = $rows->map(fn (\stdClass $r): array => [(string) $r->label, (int) $r->documents, (float) $r->revenue, (float) $r->collected])->all();

            return Excel::download(new ArrayExport($headings, $export), "ventes-{$group}-{$from}-{$to}.xlsx");
        }

        return response()->json(['data' => ['from' => $from, 'to' => $to, 'group' => $group, 'rows' => $rows]]);
    }

    /**
     * Valorisation du stock (quantité × coût d'achat) par lieu.
     */
    public function stockValuation(): JsonResponse
    {
        $rows = DB::table('stocks')
            ->join('warehouses', 'warehouses.id', '=', 'stocks.warehouse_id')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->where('warehouses.is_active', true)
            ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
            ->orderBy('warehouses.code')
            ->get([
                'warehouses.code',
                'warehouses.name',
                DB::raw('SUM(stocks.quantity) as units'),
                DB::raw('ROUND(SUM(stocks.quantity * products.cost_price), 2) as value'),
            ]);

        $total = $rows->sum(fn (\stdClass $r): float => (float) $r->value);

        return response()->json(['data' => ['warehouses' => $rows, 'total_value' => round($total, 2)]]);
    }

    /**
     * Articles dormants : aucun mouvement de sortie depuis N jours (défaut 90).
     */
    public function dormantProducts(Request $request): JsonResponse
    {
        $days = max(30, min(365, $request->integer('days', 90)));

        $activeIds = DB::table('stock_movements')
            ->join('movement_types', 'movement_types.id', '=', 'stock_movements.movement_type_id')
            ->whereIn('movement_types.code', ['out', 'transfer_out'])
            ->where('stock_movements.created_at', '>=', now()->subDays($days))
            ->distinct()
            ->pluck('stock_movements.product_id');

        $rows = DB::table('products')
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->whereNotIn('products.id', $activeIds)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.cost_price')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) > 0')
            ->orderByDesc(DB::raw('SUM(stocks.quantity * products.cost_price)'))
            ->limit(100)
            ->get([
                'products.sku',
                'products.name',
                DB::raw('COALESCE(SUM(stocks.quantity), 0) as quantity'),
                DB::raw('ROUND(COALESCE(SUM(stocks.quantity), 0) * products.cost_price, 2) as immobilized_value'),
            ]);

        return response()->json(['data' => ['days' => $days, 'rows' => $rows]]);
    }

    /**
     * Marges réalisées par article (ventes confirmées, coût CMUP actuel).
     */
    public function margins(Request $request): JsonResponse
    {
        /** @var array{from?: string|null, to?: string|null} $data */
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $data['from'] ?? now()->subDays(30)->format('Y-m-d');
        $to = $data['to'] ?? now()->format('Y-m-d');

        $rows = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.type', 'invoice')
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.confirmed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_lines.line_total) - SUM(sale_lines.quantity * products.cost_price)'))
            ->limit(100)
            ->get([
                'products.sku',
                'products.name',
                DB::raw('SUM(sale_lines.quantity) as quantity'),
                DB::raw('ROUND(SUM(sale_lines.line_total), 2) as revenue'),
                DB::raw('ROUND(SUM(sale_lines.quantity * products.cost_price), 2) as cost'),
                DB::raw('ROUND(SUM(sale_lines.line_total) - SUM(sale_lines.quantity * products.cost_price), 2) as margin'),
            ]);

        return response()->json(['data' => ['from' => $from, 'to' => $to, 'rows' => $rows]]);
    }
}
