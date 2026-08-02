<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Actions\SetProductPricesAction;
use App\Domain\Pricing\Contracts\MarginCalculatorInterface;
use App\Domain\Pricing\Contracts\ProductPriceRepositoryInterface;
use App\Domain\Pricing\DTOs\PriceLevelData;
use App\Domain\Pricing\Exceptions\InvalidPriceOrderException;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use App\Domain\Pricing\Services\ProductCostResolver;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductPricesRequest;
use App\Support\Export\HtmlTable;
use App\Support\Query\Sortable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ProductPriceController extends Controller
{
    public function __construct(
        private readonly ProductPriceRepositoryInterface $prices,
        private readonly MarginCalculatorInterface $margins,
        private readonly ProductCostResolver $cost,
    ) {}

    /**
     * Référentiel des types de prix (pour les selects).
     */
    public function priceTypes(): JsonResponse
    {
        return response()->json(['data' => PriceType::query()
            ->orderBy('rank')
            ->get(['id', 'code', 'name'])]);
    }

    public function list(Request $request): JsonResponse
    {
        $types = PriceType::query()->orderBy('rank')->get();

        $query = Product::query()
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('search')->value();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->integer('category_id') > 0, fn ($q) => $q->where('category_id', $request->integer('category_id')));

        Sortable::apply($query, $request, ['sku' => 'sku', 'name' => 'name'], 'name');

        $paginator = $query->paginate(in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20);

        /** @var list<int> $productIds */
        $productIds = array_map(fn (Product $p): int => $p->id, $paginator->items());

        $current = ProductPrice::query()
            ->whereIn('product_id', $productIds)
            ->whereNull('valid_to')
            ->get()
            ->groupBy('product_id');

        $paginator->through(function (Product $product) use ($types, $current): array {
            $byType = $current->get($product->id, collect())->keyBy('price_type_id')->all();

            $prices = [];
            foreach ($types as $type) {
                $price = array_key_exists($type->id, $byType) ? $byType[$type->id] : null;
                $prices[$type->code] = [
                    'amount' => $price !== null ? (float) $price->amount : null,
                    'min_quantity' => $price !== null ? ($price->min_quantity ?? $type->min_quantity) : $type->min_quantity,
                ];
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'prices' => $prices,
            ];
        });

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $detailId = (int) PriceType::query()->where('code', PriceType::DETAIL)->value('id');
        $semiId = (int) PriceType::query()->where('code', PriceType::SEMI_GROS)->value('id');
        $grosId = (int) PriceType::query()->where('code', PriceType::GROS)->value('id');

        $products = Product::query()
            ->with('category:id,name')
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('search')->value();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->integer('category_id') > 0, fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->orderBy('name')
            ->get();

        $current = ProductPrice::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->whereNull('valid_to')
            ->get()
            ->groupBy('product_id');

        $headings = ['Référence', 'Nom', 'Catégorie', 'Détail', 'Demi-gros', 'Gros'];

        $rows = $products->map(function (Product $p) use ($current, $detailId, $semiId, $grosId): array {
            $byType = $current->get($p->id, collect())->keyBy('price_type_id')->all();
            $amount = static function (int $typeId) use ($byType) {
                return array_key_exists($typeId, $byType) ? (float) $byType[$typeId]->amount : '';
            };

            return [
                $p->sku,
                $p->name,
                $p->category !== null ? $p->category->name : '—',
                $amount($detailId),
                $amount($semiId),
                $amount($grosId),
            ];
        })->values()->all();

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render('Tarifs de vente', $headings, $rows))->download('tarifs.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'tarifs.xlsx');
    }

    public function index(Request $request, Product $product): JsonResponse
    {
        $canViewCost = $request->user()?->can('product.view_cost_price') ?? false;
        $unitCost = $this->cost->unitCost($product);
        $current = $this->prices->currentFor($product->id)->all();

        $levels = PriceType::query()->orderBy('rank')->get()->map(function (PriceType $type) use ($current, $unitCost) {
            $price = array_key_exists($type->code, $current) ? $current[$type->code] : null;
            $amount = $price !== null ? (float) $price->amount : null;
            $minMargin = $price !== null ? (float) $price->min_margin_percent : 0.0;
            $minQuantity = $price !== null ? ($price->min_quantity ?? $type->min_quantity) : $type->min_quantity;

            return [
                'price_type_id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'min_quantity' => $minQuantity,
                'amount' => $amount,
                'min_margin_percent' => $minMargin,
                'margin_percent' => $amount !== null ? $this->margins->marginPercent($amount, $unitCost) : null,
                'floor_price' => $this->margins->floorPrice($unitCost, $minMargin),
                'below_floor' => $amount !== null && $amount < $this->margins->floorPrice($unitCost, $minMargin),
            ];
        })->all();

        return response()->json([
            'data' => [
                'product' => ['id' => $product->id, 'sku' => $product->sku, 'name' => $product->name],
                'unit_cost' => $canViewCost ? round($unitCost, 2) : null,
                'levels' => $levels,
            ],
        ]);
    }

    public function update(UpdateProductPricesRequest $request, Product $product, SetProductPricesAction $action): JsonResponse
    {
        /** @var list<array{price_type_code: string, amount: int|float, min_margin_percent?: int|float|null, min_quantity?: int|null}> $rows */
        $rows = $request->validated()['prices'];

        $levels = array_map(fn (array $row): PriceLevelData => new PriceLevelData(
            priceTypeCode: $row['price_type_code'],
            amount: (float) $row['amount'],
            minMarginPercent: (float) ($row['min_margin_percent'] ?? 0),
            minQuantity: isset($row['min_quantity']) ? (int) $row['min_quantity'] : null,
        ), $rows);

        try {
            $action->execute($product->id, $levels, $request->user()?->id);
        } catch (InvalidPriceOrderException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->index($request, $product->refresh());
    }

    public function history(Product $product): JsonResponse
    {
        $history = $this->prices->historyFor($product->id)->map(fn (ProductPrice $p): array => [
            'price_type' => $p->priceType?->name,
            'amount' => (float) $p->amount,
            'valid_from' => $p->valid_from,
            'valid_to' => $p->valid_to,
        ])->all();

        return response()->json(['data' => $history]);
    }

    /**
     * Mise à jour des tarifs en masse : variation en % sur un type de prix,
     * filtrée par catégorie. `apply=false` = prévisualisation seule.
     */
    public function bulkUpdate(Request $request, SetProductPricesAction $action): JsonResponse
    {
        /** @var array{price_type_code: string, percent: float, category_id?: int|null, apply?: bool} $data */
        $data = $request->validate([
            'price_type_code' => ['required', 'string', 'exists:price_types,code'],
            'percent' => ['required', 'numeric', 'between:-90,500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'apply' => ['sometimes', 'boolean'],
        ]);

        $type = PriceType::query()->where('code', $data['price_type_code'])->firstOrFail();
        $factor = 1 + ((float) $data['percent']) / 100;

        $prices = ProductPrice::query()
            ->with('product:id,sku,name,category_id')
            ->where('price_type_id', $type->id)
            ->whereNull('valid_to')
            ->when(($data['category_id'] ?? 0) > 0, fn ($q) => $q->whereHas(
                'product',
                fn ($p) => $p->where('category_id', $data['category_id']),
            ))
            ->get();

        $preview = $prices->map(fn (ProductPrice $price): array => [
            'product_id' => $price->product_id,
            'sku' => $price->product?->sku,
            'name' => $price->product?->name,
            'current' => (float) $price->amount,
            'next' => round((float) $price->amount * $factor, 2),
        ])->values();

        if (! ($data['apply'] ?? false)) {
            return response()->json(['data' => ['count' => $preview->count(), 'rows' => $preview->take(200)->all(), 'applied' => false]]);
        }

        $userId = $request->user()?->id;
        foreach ($prices as $price) {
            $action->execute($price->product_id, [new PriceLevelData(
                priceTypeCode: $type->code,
                amount: round((float) $price->amount * $factor, 2),
                minMarginPercent: (float) $price->min_margin_percent,
                minQuantity: $price->min_quantity,
            )], $userId);
        }

        return response()->json(['data' => ['count' => $preview->count(), 'rows' => [], 'applied' => true]]);
    }

    public function belowFloor(): JsonResponse
    {
        $rows = ProductPrice::query()
            ->join('products', 'products.id', '=', 'product_prices.product_id')
            ->join('price_types', 'price_types.id', '=', 'product_prices.price_type_id')
            ->whereNull('product_prices.valid_to')
            ->where('product_prices.min_margin_percent', '>', 0)
            ->whereRaw('product_prices.amount < products.cost_price * (1 + product_prices.min_margin_percent / 100)')
            ->orderBy('products.name')
            ->get([
                'products.id as product_id',
                'products.sku',
                'products.name',
                'price_types.name as price_type',
                'product_prices.amount',
            ]);

        return response()->json(['data' => $rows]);
    }
}
