<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\CreateProductAction;
use App\Domain\Catalog\Actions\DeleteProductAction;
use App\Domain\Catalog\Actions\SetProductPriceAction;
use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\DTOs\PricingData;
use App\Domain\Catalog\DTOs\ProductData;
use App\Domain\Catalog\Exceptions\ProductInUseException;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\ProductInsightsService;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\StockMovement;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdatePricingRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSupplierResource;
use App\Http\Resources\StockMovementResource;
use App\Imports\ArticlesImport;
use App\Support\Export\HtmlTable;
use App\Support\Query\Sortable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ProductController extends Controller
{
    /**
     * Taille de page bornée aux valeurs autorisées (20, 50, 100).
     */
    private function perPage(Request $request): int
    {
        $requested = $request->integer('per_page', 20);

        return in_array($requested, [20, 50, 100], true) ? $requested : 20;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,code'])
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('search')->value();
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('barcode', $term);
                });
            })
            ->when($request->integer('category_id') > 0, fn ($q) => $q->where('category_id', $request->integer('category_id')))
            // Stock du lieu demandé, exposé comme current_stock (écran bon de commande).
            ->when($request->integer('warehouse_id') > 0, function ($q) use ($request) {
                $q->addSelect(['current_stock' => \DB::table('stocks')
                    ->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('warehouse_id', $request->integer('warehouse_id')),
                ]);
            })
            // Articles sous leur seuil d'alerte sur le lieu demandé.
            ->when($request->boolean('below_threshold') && $request->integer('warehouse_id') > 0, function ($q) use ($request) {
                $q->whereNotNull('min_stock')
                    ->where('min_stock', '>', 0)
                    ->whereRaw(
                        'COALESCE((SELECT SUM(s.quantity) FROM stocks s WHERE s.product_id = products.id AND s.warehouse_id = ?), 0) < products.min_stock',
                        [$request->integer('warehouse_id')],
                    );
            });

        Sortable::apply($products, $request, [
            'sku' => 'sku',
            'name' => 'name',
            'min_stock' => 'min_stock',
            'sale_price' => 'sale_price',
        ], 'name');

        return ProductResource::collection($products->paginate($this->perPage($request)));
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute(ProductData::fromArray($request->validated()));

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make(
            $product->load([
                'category:id,name',
                'brand:id,name',
                'unit:id,code,name',
                'images:id,product_id,path,is_main,position',
                'attributes:id,product_id,name,value,position',
            ]),
        );
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): ProductResource
    {
        $updated = $action->execute($product, ProductData::fromArray($request->validated()));

        return ProductResource::make($updated);
    }

    public function updatePricing(UpdatePricingRequest $request, Product $product, SetProductPriceAction $action): ProductResource
    {
        $updated = $action->execute($product, PricingData::fromArray($request->validated()));

        return ProductResource::make($updated);
    }

    public function destroy(Product $product, DeleteProductAction $action): JsonResponse|Response
    {
        try {
            $action->execute($product);
        } catch (ProductInUseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }

    public function bulkDestroy(Request $request, DeleteProductAction $action): JsonResponse
    {
        /** @var array{ids: list<int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;
        $blocked = [];

        foreach (Product::query()->whereIn('id', $validated['ids'])->get() as $product) {
            try {
                $action->execute($product);
                $deleted++;
            } catch (ProductInUseException $e) {
                $blocked[] = ['id' => $product->id, 'name' => $product->name, 'reason' => $e->getMessage()];
            }
        }

        return response()->json(['data' => ['deleted' => $deleted, 'blocked' => $blocked]]);
    }

    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $withCost = $request->user()?->can('product.view_cost_price') ?? false;

        $headings = ['Référence', 'Nom', 'Catégorie', 'Seuil min', 'Prix de vente'];
        if ($withCost) {
            $headings[] = "Prix d'achat";
        }
        $headings[] = 'Statut';

        $rows = Product::query()
            ->with('category:id,name')
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('search')->value();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->integer('category_id') > 0, fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) use ($withCost): array {
                $row = [
                    $p->sku,
                    $p->name,
                    $p->category !== null ? $p->category->name : '—',
                    $p->min_stock ?? '',
                    (float) $p->sale_price,
                ];
                if ($withCost) {
                    $row[] = (float) $p->cost_price;
                }
                $row[] = $p->is_active ? 'Actif' : 'Inactif';

                return $row;
            })
            ->values()
            ->all();

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render('Articles', $headings, $rows))->download('articles.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'articles.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $import = new ArticlesImport;
        Excel::import($import, $request->file('file'));

        return response()->json([
            'data' => [
                'created' => $import->created,
                'updated' => $import->updated,
                'skipped' => $import->skipped,
            ],
        ]);
    }

    /**
     * Stock du produit dans tous les lieux (ou un lieu spécifique).
     * GET /products/{id}/stock?warehouse_id=1
     */
    public function stock(Request $request, Product $product): JsonResponse
    {
        $query = Stock::query()
            ->with('warehouse:id,code,name')
            ->where('product_id', $product->id);

        if ($request->integer('warehouse_id') > 0) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        // Le scope de lieu s'applique : un responsable ne totalise que le sien.
        $stocks = $query->get();

        $lieux = $stocks->map(fn (Stock $s): array => [
            'id' => $s->id,
            'warehouse_id' => $s->warehouse_id,
            'warehouse_name' => $s->warehouse?->code.' · '.$s->warehouse?->name,
            'quantity' => (int) $s->quantity,
            'reserved' => (int) $s->reserved_quantity,
            'available' => (int) $s->quantity - (int) $s->reserved_quantity,
            'valuation' => number_format((float) $s->quantity * (float) $s->average_cost, 2, '.', ''),
        ])->values()->all();

        $total = (int) $stocks->sum('quantity');
        $reserve = (int) $stocks->sum('reserved_quantity');
        $valeur = $stocks->sum(fn (Stock $s): float => (float) $s->quantity * (float) $s->average_cost);

        return response()->json(['data' => [
            'product_id' => $product->id,
            'total_quantity' => $total,
            'total_reserved' => $reserve,
            'total_available' => $total - $reserve,
            'total_valuation' => number_format((float) $valeur, 2, '.', ''),
            'in_transit' => $this->quantiteEnTransit($product),
            'locations' => $lieux,
        ]]);
    }

    /**
     * Quantité expédiée mais pas encore réceptionnée, tous transferts confondus.
     */
    private function quantiteEnTransit(Product $product): int
    {
        return (int) DB::table('transfer_lines')
            ->join('transfers', 'transfers.id', '=', 'transfer_lines.transfer_id')
            ->join('transfer_statuses', 'transfer_statuses.id', '=', 'transfers.transfer_status_id')
            ->where('transfer_lines.product_id', $product->id)
            ->where('transfer_statuses.code', 'in_transit')
            ->sum(DB::raw('transfer_lines.quantity_sent - transfer_lines.quantity_received'));
    }

    /**
     * Mouvements de stock du produit avec filtres.
     * GET /products/{id}/movements?warehouse_id=&type=&date_from=&page=
     */
    public function movements(Request $request, Product $product): JsonResponse
    {
        $paginator = StockMovement::query()
            ->with(['movementType:id,code,name'])
            ->where('product_id', $product->id)
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->whereHas('movementType', fn ($t) => $t->where('code', $request->string('type')->value())))
            ->when($request->string('date_from')->isNotEmpty(), fn ($q) => $q->where('created_at', '>=', $request->string('date_from')->value()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return response()->json([
            'data' => StockMovementResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Fournisseurs référencés pour ce produit.
     * GET /products/{id}/suppliers
     */
    public function suppliers(Product $product): AnonymousResourceCollection
    {
        $suppliers = $product->suppliers()
            ->orderBy('product_supplier.supplier_reference')
            ->get();

        return ProductSupplierResource::collection($suppliers);
    }

    /**
     * Statistiques du produit (pour une période donnée).
     * GET /products/{id}/statistics?period=12m
     */
    public function statistics(Request $request, Product $product, ProductInsightsService $insights): JsonResponse
    {
        $period = $request->string('period')->value() !== '' ? $request->string('period')->value() : '12m';

        return response()->json(['data' => $insights->statistics($product, $period)]);
    }

    /**
     * Historique transverse : ce que l'article a vécu dans les autres modules.
     */
    public function history(Product $product, ProductInsightsService $insights): JsonResponse
    {
        return response()->json(['data' => $insights->history($product)]);
    }
}
