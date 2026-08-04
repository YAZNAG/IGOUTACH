<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Support\Export\HtmlTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Coûts des articles : CMUP global (moyenne pondérée des lieux), valeur de
 * stock, dernier prix d'achat et marge du prix détail en vigueur.
 */
final class ProductCostController extends Controller
{
    /**
     * @return Builder<Product>
     */
    private function baseQuery(Request $request): Builder
    {
        return Product::query()
            ->with('category:id,name')
            ->select('products.*')
            // Quantité totale et valeur (qté × CMUP du lieu) sur tous les lieux.
            ->selectSub(
                DB::table('stocks')->selectRaw('COALESCE(SUM(quantity), 0)')->whereColumn('product_id', 'products.id'),
                'total_quantity',
            )
            ->selectSub(
                DB::table('stocks')->selectRaw('COALESCE(SUM(quantity * average_cost), 0)')->whereColumn('product_id', 'products.id'),
                'stock_value',
            )
            // Dernier prix d'achat réellement payé (réceptions).
            ->selectSub(
                DB::table('goods_receipt_lines')->select('unit_price')->whereColumn('product_id', 'products.id')->orderByDesc('id')->limit(1),
                'last_purchase_price',
            )
            ->selectSub(
                DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->select('goods_receipts.received_at')
                    ->whereColumn('goods_receipt_lines.product_id', 'products.id')
                    ->orderByDesc('goods_receipt_lines.id')
                    ->limit(1),
                'last_purchase_at',
            )
            // Prix détail en vigueur.
            ->selectSub(
                DB::table('product_prices')
                    ->join('price_types', 'price_types.id', '=', 'product_prices.price_type_id')
                    ->select('product_prices.amount')
                    ->whereColumn('product_prices.product_id', 'products.id')
                    ->where('price_types.code', 'detail')
                    ->whereNull('product_prices.valid_to')
                    ->limit(1),
                'detail_price',
            )
            ->when($request->string('search')->isNotEmpty(), function (Builder $q) use ($request): void {
                $term = $request->string('search')->value();
                $q->where(fn (Builder $sub) => $sub->where('products.name', 'like', "%{$term}%")->orWhere('products.sku', 'like', "%{$term}%"));
            })
            ->when($request->integer('category_id') > 0, fn (Builder $q) => $q->where('category_id', $request->integer('category_id')));
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Product $product): array
    {
        $qty = (int) $product->getAttribute('total_quantity');
        $value = (float) $product->getAttribute('stock_value');
        // CMUP global = valeur totale / quantité totale ; repli sur cost_price.
        $cmup = $qty > 0 ? $value / $qty : (float) $product->cost_price;
        $detail = $product->getAttribute('detail_price') !== null ? (float) $product->getAttribute('detail_price') : null;
        $margin = $detail !== null && $cmup > 0 ? (($detail - $cmup) / $cmup) * 100 : null;

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name,
            'total_quantity' => $qty,
            'cmup' => round($cmup, 2),
            'stock_value' => round($value, 2),
            'last_purchase_price' => $product->getAttribute('last_purchase_price') !== null
                ? (float) $product->getAttribute('last_purchase_price')
                : null,
            'last_purchase_at' => $product->getAttribute('last_purchase_at') !== null
                ? substr((string) $product->getAttribute('last_purchase_at'), 0, 10)
                : null,
            'detail_price' => $detail,
            'margin_percent' => $margin !== null ? round($margin, 1) : null,
            'below_cost' => $detail !== null && $detail < $cmup,
        ];
    }

    /**
     * GET /product-costs — liste paginée + totaux sur l'ensemble filtré.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request);

        $totals = DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request): void {
                $term = $request->string('search')->value();
                $q->where(fn ($sub) => $sub->where('products.name', 'like', "%{$term}%")->orWhere('products.sku', 'like', "%{$term}%"));
            })
            ->when($request->integer('category_id') > 0, fn ($q) => $q->where('products.category_id', $request->integer('category_id')))
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as total_quantity, COALESCE(SUM(stocks.quantity * stocks.average_cost), 0) as total_value')
            ->first();

        $sort = $request->string('sort')->value();
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';
        $query->orderBy(match ($sort) {
            'sku' => 'sku',
            'stock_value' => 'stock_value',
            'total_quantity' => 'total_quantity',
            default => 'name',
        }, $direction);

        $perPage = in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20;
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => array_map(fn (Product $p): array => $this->toRow($p), $paginator->items()),
            'totals' => [
                'total_quantity' => (int) ($totals->total_quantity ?? 0),
                'total_value' => round((float) ($totals->total_value ?? 0), 2),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /product-costs/export?format=pdf|xlsx — mêmes filtres.
     */
    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $products = $this->baseQuery($request)->orderBy('name')->get();

        $headings = ['Référence', 'Article', 'Catégorie', 'Stock total', 'CMUP (DH)', 'Valeur stock (DH)', 'Dernier achat (DH)', 'Date dernier achat', 'Prix détail (DH)', 'Marge (%)'];

        $rows = $products->map(function (Product $p): array {
            $row = $this->toRow($p);

            return [
                $row['sku'],
                $row['name'],
                $row['category'] ?? '—',
                $row['total_quantity'],
                number_format($row['cmup'], 2, '.', ''),
                number_format($row['stock_value'], 2, '.', ''),
                $row['last_purchase_price'] !== null ? number_format($row['last_purchase_price'], 2, '.', '') : '—',
                $row['last_purchase_at'] ?? '—',
                $row['detail_price'] !== null ? number_format($row['detail_price'], 2, '.', '') : '—',
                $row['margin_percent'] !== null ? number_format($row['margin_percent'], 1, '.', '') : '—',
            ];
        })->values()->all();

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render('Coûts des articles (CMUP)', $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download('IGOUTECH_couts-articles.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'IGOUTECH_couts-articles.xlsx');
    }
}
