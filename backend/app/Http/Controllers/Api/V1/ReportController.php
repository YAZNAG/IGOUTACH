<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exports\ArrayExport;
use App\Support\Export\HtmlTable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Domain\Sales\Services\ProfitReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

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
    /**
     * L'activité de la période, découpée par lieu, client, article et
     * fournisseur.
     *
     * Une seule source pour deux écrans : le chiffre d'affaires et le
     * bénéfice sortent de la même requête. Deux endpoints auraient fini par
     * diverger, et personne n'aurait su lequel croire.
     *
     * GET /reports/breakdown?from=&to=
     */
    public function breakdown(Request $request, ProfitReportService $benefices): JsonResponse
    {
        /** @var array{from?: string|null, to?: string|null} $data */
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $du = $data['from'] ?? now()->startOfMonth()->format('Y-m-d');
        $au = $data['to'] ?? now()->format('Y-m-d');

        // La période précédente, de même longueur et immédiatement avant :
        // un chiffre sans point de comparaison ne dit pas si l'on progresse.
        $debut = \Illuminate\Support\Carbon::parse($du);
        $fin = \Illuminate\Support\Carbon::parse($au);
        $jours = $debut->diffInDays($fin) + 1;
        $duPrec = $debut->copy()->subDays($jours)->format('Y-m-d');
        $auPrec = $debut->copy()->subDay()->format('Y-m-d');

        $charges = $benefices->charges($du, $au);
        $chargesPrec = $benefices->charges($duPrec, $auPrec);
        $totaux = $benefices->totaux($du, $au);
        $totauxPrec = $benefices->totaux($duPrec, $auPrec);

        $donnees = [
            'from' => $du,
            'to' => $au,
            'totals' => $totaux,
            'expenses' => $charges,
            // Marge brute moins charges : le seul chiffre qui dise ce qui
            // reste vraiment.
            'net_result' => round($totaux['profit'] - $charges['total'], 2),
            'cash' => $benefices->encaissements($du, $au),
            'series' => $benefices->serie($du, $au),
            'previous' => [
                'from' => $duPrec,
                'to' => $auPrec,
                'revenue' => $totauxPrec['revenue'],
                'cost' => $totauxPrec['cost'],
                'profit' => $totauxPrec['profit'],
                'documents' => $totauxPrec['documents'],
                'expenses' => $chargesPrec['total'],
                'net_result' => round($totauxPrec['profit'] - $chargesPrec['total'], 2),
            ],
            'by_warehouse' => $benefices->parLieu($du, $au),
            'by_customer' => $benefices->parClient($du, $au),
            'by_product' => $benefices->parArticle($du, $au),
            'by_supplier' => $benefices->parFournisseur($du, $au),
            'missing_cost' => $benefices->coutsManquants($du, $au),
        ];

        // Le bénéfice se déduit du chiffre d'affaires moins le coût : le
        // laisser passer contournerait « product.view_cost_price ».
        if (! ($request->user()?->can('product.view_cost_price') ?? false)) {
            unset(
                $donnees['totals']['cost'],
                $donnees['totals']['profit'],
                $donnees['totals']['margin_percent'],
                $donnees['missing_cost'],
                // Le résultat net se ramène au bénéfice dès qu'on connaît les
                // charges : le laisser passer contournerait la permission.
                $donnees['net_result'],
                $donnees['previous']['cost'],
                $donnees['previous']['profit'],
                $donnees['previous']['net_result'],
            );

            $donnees['series'] = array_map(static function (array $jour): array {
                unset($jour['cost'], $jour['profit']);

                return $jour;
            }, $donnees['series']);

            foreach (['by_warehouse', 'by_customer', 'by_product', 'by_supplier'] as $bloc) {
                $donnees[$bloc] = array_map(static function (array $ligne): array {
                    unset($ligne['cost'], $ligne['profit'], $ligne['margin_percent']);

                    return $ligne;
                }, $donnees[$bloc]);
            }
        }

        return response()->json(['data' => $donnees]);
    }

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

    /**
     * Bénéfice de tous les articles vendus sur une période, un jour ou un lieu.
     * GET /reports/profit-by-product?from&to&day&warehouse_id&q&format=xlsx|pdf
     *
     * Le jour, s'il est donné, l'emporte sur la période : c'est le filtre
     * « qu'est-ce qui a rapporté aujourd'hui ».
     */
    public function profitByProduct(Request $request, ProfitReportService $benefices): JsonResponse|BinaryFileResponse|Response
    {
        // Le bénéfice se déduit des prix d'achat : sans le droit de les voir,
        // la liste n'a rien à montrer, et l'export encore moins.
        if (! ($request->user()?->can('product.view_cost_price') ?? false)) {
            return response()->json(['message' => 'Le bénéfice par article demande le droit de voir les prix d’achat.'], 403);
        }

        /** @var array{from?: string|null, to?: string|null, day?: string|null, warehouse_id?: int|null, q?: string|null, format?: string|null} $data */
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'day' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'format' => ['nullable', 'in:xlsx,pdf'],
        ]);

        $du = $data['from'] ?? now()->startOfMonth()->format('Y-m-d');
        $au = $data['to'] ?? now()->format('Y-m-d');
        if (! empty($data['day'])) {
            $du = $au = \Illuminate\Support\Carbon::parse($data['day'])->format('Y-m-d');
        }
        $lieu = (int) ($data['warehouse_id'] ?? 0);
        $recherche = trim((string) ($data['q'] ?? ''));

        $lignes = $benefices->tousLesArticles($du, $au, $lieu, $recherche);

        $totaux = [
            'products' => count($lignes),
            'quantity' => (int) array_sum(array_column($lignes, 'quantity')),
            'revenue' => round(array_sum(array_column($lignes, 'revenue')), 2),
            'cost' => round(array_sum(array_column($lignes, 'cost')), 2),
            'profit' => round(array_sum(array_column($lignes, 'profit')), 2),
        ];
        $totaux['margin_percent'] = $totaux['revenue'] > 0 ? round($totaux['profit'] / $totaux['revenue'] * 100, 1) : null;

        $format = $data['format'] ?? null;
        if ($format === null) {
            return response()->json(['data' => ['from' => $du, 'to' => $au, 'rows' => $lignes, 'totals' => $totaux]]);
        }

        $titre = $du === $au ? "Bénéfice par article — {$du}" : "Bénéfice par article — du {$du} au {$au}";
        $entetes = ['Référence', 'Article', 'Catégorie', 'Qté vendue', 'Factures', 'CA (DH)', 'Coût (DH)', 'Bénéfice (DH)', 'Bénéfice / unité', 'Marge %'];
        $rangs = array_map(static fn (array $l): array => [
            $l['sku'],
            $l['name'],
            $l['category'] ?? '—',
            $l['quantity'],
            $l['documents'],
            number_format($l['revenue'], 2, '.', ''),
            number_format($l['cost'], 2, '.', ''),
            number_format($l['profit'], 2, '.', ''),
            $l['unit_profit'] !== null ? number_format($l['unit_profit'], 2, '.', '') : '—',
            $l['margin_percent'] !== null ? number_format($l['margin_percent'], 1, '.', '') : '—',
        ], $lignes);
        $rangs[] = ['', 'TOTAL', '', $totaux['quantity'], '', number_format($totaux['revenue'], 2, '.', ''), number_format($totaux['cost'], 2, '.', ''), number_format($totaux['profit'], 2, '.', ''), '', $totaux['margin_percent'] !== null ? number_format($totaux['margin_percent'], 1, '.', '') : '—'];

        $nom = 'IGOUTECH_benefice-articles_'.($du === $au ? $du : "{$du}_{$au}");

        if ($format === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render($titre, $entetes, $rangs))
                ->setPaper('a4', 'landscape')
                ->download($nom.'.pdf');
        }

        return Excel::download(new ArrayExport($entetes, $rangs), $nom.'.xlsx');
    }
}
