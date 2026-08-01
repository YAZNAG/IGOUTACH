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
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdatePricingRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Imports\ArticlesImport;
use App\Support\Export\HtmlTable;
use App\Support\Query\Sortable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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
            ->when($request->integer('category_id') > 0, fn ($q) => $q->where('category_id', $request->integer('category_id')));

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
            $product->load(['category:id,name', 'brand:id,name', 'unit:id,code']),
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
}
