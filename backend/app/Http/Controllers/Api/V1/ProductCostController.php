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
                DB::table('stocks')->selectRaw('COALESCE(SUM(quantity), 0) * products.cost_price')->whereColumn('product_id', 'products.id'),
                'stock_value',
            )
            // Dernier prix d'achat réellement payé (réceptions). Le classement
            // suit la DATE de réception, pas l'ordre de saisie : une réception
            // enregistrée après coup pour une date ancienne ne doit pas passer
            // pour le dernier achat. C'est aussi la règle que suit le report
            // sur la fiche article, les deux chiffres restent donc cohérents.
            ->selectSub(
                DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->select('goods_receipt_lines.unit_price')
                    ->whereColumn('goods_receipt_lines.product_id', 'products.id')
                    ->orderByDesc('goods_receipts.received_at')
                    ->orderByDesc('goods_receipt_lines.id')
                    ->limit(1),
                'last_purchase_price',
            )
            ->selectSub(
                DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->select('goods_receipts.received_at')
                    ->whereColumn('goods_receipt_lines.product_id', 'products.id')
                    ->orderByDesc('goods_receipts.received_at')
                    ->orderByDesc('goods_receipt_lines.id')
                    ->limit(1),
                'last_purchase_at',
            )
            // Le fournisseur de cette même réception : savoir à quel prix on a
            // acheté sans savoir chez qui n'aide pas à renégocier.
            ->selectSub(
                DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->leftJoin('suppliers', 'suppliers.id', '=', 'goods_receipts.supplier_id')
                    ->select('suppliers.name')
                    ->whereColumn('goods_receipt_lines.product_id', 'products.id')
                    ->orderByDesc('goods_receipts.received_at')
                    ->orderByDesc('goods_receipt_lines.id')
                    ->limit(1),
                'last_purchase_supplier',
            )
            // Combien de fois l'article a été acheté : une seule réception ne
            // dit rien d'une tendance, dix en disent long.
            ->selectSub(
                DB::table('goods_receipt_lines')->selectRaw('COUNT(*)')->whereColumn('product_id', 'products.id'),
                'purchase_count',
            )
            // Le numéro du bon, pour remonter à la pièce.
            ->selectSub(
                DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->select('goods_receipts.number')
                    ->whereColumn('goods_receipt_lines.product_id', 'products.id')
                    ->orderByDesc('goods_receipts.received_at')
                    ->orderByDesc('goods_receipt_lines.id')
                    ->limit(1),
                'last_purchase_number',
            )
            // Réglé ou non : l'information est utile pour le suivi du
            // fournisseur, mais elle ne change rien au prix d'achat — la
            // marchandise a été reçue à ce prix, payée ou pas.
            ->selectSub(
                DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->select('goods_receipts.payment_status')
                    ->whereColumn('goods_receipt_lines.product_id', 'products.id')
                    ->orderByDesc('goods_receipts.received_at')
                    ->orderByDesc('goods_receipt_lines.id')
                    ->limit(1),
                'last_purchase_payment_status',
            )
            // Les trois tarifs en vigueur. Les voir ensemble à côté du prix
            // payé est la seule façon de juger d'un coup d'œil si la grille
            // tient encore après une hausse du fournisseur.
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
            ->selectSub(
                DB::table('product_prices')
                    ->join('price_types', 'price_types.id', '=', 'product_prices.price_type_id')
                    ->select('product_prices.amount')
                    ->whereColumn('product_prices.product_id', 'products.id')
                    ->where('price_types.code', 'semi_gros')
                    ->whereNull('product_prices.valid_to')
                    ->limit(1),
                'semi_gros_price',
            )
            ->selectSub(
                DB::table('product_prices')
                    ->join('price_types', 'price_types.id', '=', 'product_prices.price_type_id')
                    ->select('product_prices.amount')
                    ->whereColumn('product_prices.product_id', 'products.id')
                    ->where('price_types.code', 'gros')
                    ->whereNull('product_prices.valid_to')
                    ->limit(1),
                'gros_price',
            )
            ->when($request->string('search')->isNotEmpty(), function (Builder $q) use ($request): void {
                $term = $request->string('search')->value();
                $q->where(fn (Builder $sub) => $sub->where('products.name', 'like', "%{$term}%")->orWhere('products.sku', 'like', "%{$term}%"));
            })
            ->when($request->integer('category_id') > 0, fn (Builder $q) => $q->where('category_id', $request->integer('category_id')));
    }

    /**
     * Marge d'un tarif sur le prix d'achat, en pourcentage.
     *
     * Null quand l'un des deux manque : afficher « 0 % » sur un article sans
     * tarif laisserait croire qu'il se vend à prix coûtant.
     */
    private function marge(?float $vente, float $achat): ?float
    {
        if ($vente === null || $vente <= 0 || $achat <= 0) {
            return null;
        }

        return round((($vente - $achat) / $achat) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Product $product): array
    {
        $qty = (int) $product->getAttribute('total_quantity');
        $value = (float) $product->getAttribute('stock_value');
        // CMUP global = valeur totale / quantité totale ; repli sur cost_price.
        // Le cout d'achat de la fiche, tenu a jour par la reception. Il
        // remplace le CMUP partout : deux couts pour la meme marchandise
        // obligeaient a choisir lequel croire.
        $cmup = round((float) $product->cost_price, 2);
        $detail = $product->getAttribute('detail_price') !== null ? (float) $product->getAttribute('detail_price') : null;
        $semiGros = $product->getAttribute('semi_gros_price') !== null ? (float) $product->getAttribute('semi_gros_price') : null;
        $gros = $product->getAttribute('gros_price') !== null ? (float) $product->getAttribute('gros_price') : null;
        $margin = $this->marge($detail, $cmup);

        // Le prix payé au dernier bon prime sur celui de la fiche : c'est lui
        // qui vient d'être déboursé. Sans réception, la fiche reste la seule
        // source connue.
        $achat = $product->getAttribute('last_purchase_price') !== null
            ? (float) $product->getAttribute('last_purchase_price')
            : (float) $product->cost_price;

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name,
            'total_quantity' => $qty,
            // Le prix d'achat de la fiche, distinct du CMUP : le premier est
            // saisi, le second constate. Les voir cote a cote est le seul
            // moyen de reperer une fiche restee sur un ancien prix.
            'purchase_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
            // Conserve sous ce nom le temps que les applications installees
            // se mettent a jour : il porte desormais le cout d'achat.
            'cmup' => round($cmup, 2),
            'stock_value' => round($value, 2),
            'last_purchase_price' => $product->getAttribute('last_purchase_price') !== null
                ? (float) $product->getAttribute('last_purchase_price')
                : null,
            'last_purchase_at' => $product->getAttribute('last_purchase_at') !== null
                ? substr((string) $product->getAttribute('last_purchase_at'), 0, 10)
                : null,
            'last_purchase_supplier' => $product->getAttribute('last_purchase_supplier'),
            'purchase_count' => (int) $product->getAttribute('purchase_count'),
            'detail_price' => $detail,
            'margin_percent' => $margin !== null ? round($margin, 1) : null,
            'below_cost' => $detail !== null && $detail < $cmup,

            // Le prix d'achat retenu pour juger les marges : celui du dernier
            // bon de réception s'il existe, sinon celui de la fiche. C'est le
            // prix payé qui fait foi, réglé ou non.
            'applied_purchase_price' => $achat > 0 ? round($achat, 2) : null,
            'last_purchase_number' => $product->getAttribute('last_purchase_number'),
            'last_purchase_payment_status' => $product->getAttribute('last_purchase_payment_status'),
            'semi_gros_price' => $semiGros,
            'gros_price' => $gros,
            // Marge exprimée sur le prix d'achat : un article acheté 16 et
            // vendu 28,80 affiche 80 %. C'est le coefficient que l'on
            // manipule au quotidien, pas la part du prix de vente.
            'margin_detail' => $this->marge($detail, $achat),
            'margin_semi_gros' => $this->marge($semiGros, $achat),
            'margin_gros' => $this->marge($gros, $achat),
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
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as total_quantity, COALESCE(SUM(stocks.quantity * products.cost_price), 0) as total_value')
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

        $headings = ['Référence', 'Article', 'Catégorie', 'Stock total', "Prix d'achat (DH)", 'CMUP (DH)', 'Valeur stock (DH)', 'Dernier achat (DH)', 'Date dernier achat', 'Prix détail (DH)', 'Marge (%)'];

        $rows = $products->map(function (Product $p): array {
            $row = $this->toRow($p);

            return [
                $row['sku'],
                $row['name'],
                $row['category'] ?? '—',
                $row['total_quantity'],
                $row['purchase_price'] !== null ? number_format($row['purchase_price'], 2, '.', '') : '—',
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
