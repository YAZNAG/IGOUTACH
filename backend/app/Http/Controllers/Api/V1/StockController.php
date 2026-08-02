<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Actions\EntryStockAction;
use App\Domain\Stock\Actions\IssueStockAction;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockEntryRequest;
use App\Http\Requests\StockIssueRequest;
use App\Models\User;
use App\Support\Export\HtmlTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class StockController extends Controller
{
    private function perPage(Request $request): int
    {
        $requested = $request->integer('per_page', 50);

        return in_array($requested, [20, 50, 100, 500, 1000], true) ? $requested : 50;
    }

    /**
     * Stock d'un lieu : TOUS les articles avec leur quantité (0 si absent).
     */
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');

        $paginator = DB::table('products')
            ->leftJoin('stocks', function ($join) use ($warehouseId): void {
                $join->on('stocks.product_id', '=', 'products.id')->where('stocks.warehouse_id', '=', $warehouseId);
            })
            ->whereNull('products.deleted_at')
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('q')->value();
                $query->where(fn ($w) => $w->where('products.name', 'like', "%{$term}%")->orWhere('products.sku', 'like', "%{$term}%"));
            })
            ->orderByDesc(DB::raw('COALESCE(stocks.quantity, 0)'))
            ->orderBy('products.name')
            ->select(
                'products.id as product_id',
                'products.sku',
                'products.name',
                'products.min_stock',
                DB::raw('COALESCE(stocks.quantity, 0) as quantity'),
                DB::raw('COALESCE(stocks.average_cost, 0) as average_cost'),
            )
            ->paginate($this->perPage($request));

        $paginator->through(function (\stdClass $row): array {
            $qty = (int) $row->quantity;
            $min = (int) ($row->min_stock ?? 0);
            $avg = (float) $row->average_cost;

            return [
                'product_id' => (int) $row->product_id,
                'sku' => $row->sku,
                'name' => $row->name,
                'quantity' => $qty,
                'average_cost' => round($avg, 2),
                'value' => round($qty * $avg, 2),
                'min_stock' => $min,
                'status' => $qty <= 0 ? 'rupture' : ($min > 0 && $qty < $min ? 'low' : 'ok'),
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

    /**
     * Journal des mouvements de stock (append-only).
     */
    public function movements(Request $request): JsonResponse
    {
        $paginator = StockMovement::withoutGlobalScopes()
            ->with(['product:id,sku,name', 'movementType:id,name,code,sign'])
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->integer('product_id') > 0, fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->whereHas('movementType', fn ($t) => $t->where('code', $request->string('type')->value())))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        /** @var array<int, string> $userNames */
        $userNames = User::query()
            ->whereIn('id', collect($paginator->items())->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id')
            ->all();

        $paginator->through(fn (StockMovement $m): array => [
            'id' => $m->id,
            'created_at' => $m->created_at,
            'sku' => $m->product?->sku,
            'name' => $m->product?->name,
            'type' => $m->movementType?->name,
            'type_code' => $m->movementType?->code,
            'quantity' => (int) $m->quantity,
            'balance_after' => (int) $m->balance_after,
            'note' => $m->note,
            'user' => $m->user_id !== null ? ($userNames[$m->user_id] ?? null) : null,
        ]);

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

    /**
     * Types de mouvement (pour les filtres du journal).
     */
    public function movementTypes(): JsonResponse
    {
        return response()->json([
            'data' => MovementType::query()->orderBy('id')->get(['code', 'name', 'sign']),
        ]);
    }

    /**
     * Export du stock d'un lieu (Excel ou PDF).
     */
    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        $warehouse = Warehouse::query()->find($warehouseId);
        $label = $warehouse !== null ? $warehouse->code.' — '.$warehouse->name : 'Lieu';

        $headings = ['Référence', 'Article', 'Quantité', 'Seuil', 'Valeur (DH)', 'Statut'];

        $rows = DB::table('products')
            ->leftJoin('stocks', function ($join) use ($warehouseId): void {
                $join->on('stocks.product_id', '=', 'products.id')->where('stocks.warehouse_id', '=', $warehouseId);
            })
            ->whereNull('products.deleted_at')
            ->orderByDesc(DB::raw('COALESCE(stocks.quantity, 0)'))
            ->orderBy('products.name')
            ->get([
                'products.sku',
                'products.name',
                'products.min_stock',
                DB::raw('COALESCE(stocks.quantity, 0) as quantity'),
                DB::raw('COALESCE(stocks.average_cost, 0) as average_cost'),
            ])
            ->map(function (\stdClass $row): array {
                $qty = (int) $row->quantity;
                $min = (int) ($row->min_stock ?? 0);
                $avg = (float) $row->average_cost;
                $status = $qty <= 0 ? 'Rupture' : ($min > 0 && $qty < $min ? 'Sous seuil' : 'OK');

                return [
                    (string) $row->sku,
                    (string) $row->name,
                    $qty,
                    $min,
                    round($qty * $avg, 2),
                    $status,
                ];
            })
            ->values()
            ->all();

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render('Stock — '.$label, $headings, $rows))->download('stock.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'stock.xlsx');
    }

    /**
     * Matrice article × lieu : quantités de chaque article dans tous les lieux.
     */
    public function matrix(Request $request): JsonResponse
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $paginator = Product::query()
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('q')->value();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        /** @var list<int> $productIds */
        $productIds = array_map(fn (Product $p): int => $p->id, $paginator->items());

        $map = $this->quantitiesByProductAndWarehouse($productIds);

        // Marchandise en transit : lignes des transferts non encore reçus.
        $transit = [];
        DB::table('transfer_lines')
            ->join('transfers', 'transfers.id', '=', 'transfer_lines.transfer_id')
            ->join('transfer_statuses', 'transfer_statuses.id', '=', 'transfers.transfer_status_id')
            ->where('transfer_statuses.code', 'in_transit')
            ->whereIn('transfer_lines.product_id', $productIds)
            ->groupBy('transfer_lines.product_id')
            ->get(['transfer_lines.product_id', DB::raw('SUM(transfer_lines.quantity_sent) as qty')])
            ->each(function (\stdClass $row) use (&$transit): void {
                $transit[(int) $row->product_id] = (int) $row->qty;
            });

        $paginator->through(function (Product $product) use ($warehouses, $map, $transit): array {
            $quantities = [];
            $total = 0;
            foreach ($warehouses as $warehouse) {
                $qty = $map[$product->id][$warehouse->id] ?? 0;
                $quantities[(string) $warehouse->id] = $qty;
                $total += $qty;
            }

            $inTransit = $transit[$product->id] ?? 0;
            $total += $inTransit;

            return [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'in_transit' => $inTransit,
                'quantities' => $quantities,
                'total' => $total,
            ];
        });

        return response()->json([
            'warehouses' => $warehouses,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Export de la matrice article × lieu (Excel ou PDF).
     */
    public function matrixExport(Request $request): BinaryFileResponse|HttpResponse
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $products = Product::query()
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request) {
                $term = $request->string('q')->value();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        /** @var list<int> $productIds */
        $productIds = $products->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        $map = $this->quantitiesByProductAndWarehouse($productIds);

        $headings = ['Référence', 'Article'];
        foreach ($warehouses as $warehouse) {
            $headings[] = $warehouse->code;
        }
        $headings[] = 'Total';

        $rows = $products->map(function (Product $product) use ($warehouses, $map): array {
            $row = [$product->sku, $product->name];
            $total = 0;
            foreach ($warehouses as $warehouse) {
                $qty = $map[$product->id][$warehouse->id] ?? 0;
                $row[] = $qty;
                $total += $qty;
            }
            $row[] = $total;

            return $row;
        })->values()->all();

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render('Stock — tous les lieux', $headings, $rows))->download('stock-tous-lieux.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'stock-tous-lieux.xlsx');
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, array<int, int>>
     */
    private function quantitiesByProductAndWarehouse(array $productIds): array
    {
        $map = [];

        DB::table('stocks')
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'warehouse_id', 'quantity'])
            ->each(function (\stdClass $row) use (&$map): void {
                $map[(int) $row->product_id][(int) $row->warehouse_id] = (int) $row->quantity;
            });

        return $map;
    }

    /**
     * Bon d'entrée : entrée manuelle de stock dans un lieu.
     */
    public function entry(StockEntryRequest $request, EntryStockAction $action): JsonResponse
    {
        /** @var array{warehouse_id: int, date: string, lines: list<array{product_id: int, quantity: int, unit_cost?: float|int|null, note?: string|null}>} $data */
        $data = $request->validated();

        $movements = $action->execute($data['warehouse_id'], $data['date'], $data['lines'], $request->user()?->id);

        return response()->json(['data' => ['count' => count($movements)]], 201);
    }

    /**
     * Bon de sortie : sortie manuelle de stock.
     */
    public function issue(StockIssueRequest $request, IssueStockAction $action): JsonResponse
    {
        /** @var array{warehouse_id: int, reason_code: string, lines: list<array{product_id: int, quantity: int, note?: string|null}>} $data */
        $data = $request->validated();

        try {
            $movements = $action->execute($data['warehouse_id'], $data['reason_code'], $data['lines'], $request->user()?->id);
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['count' => count($movements)]], 201);
    }

    /**
     * Ajustement direct (±) avec motif obligatoire.
     */
    public function adjust(Request $request, StockWriterInterface $writer): JsonResponse
    {
        /** @var array{warehouse_id: int, product_id: int, quantity: int, reason: string} $data */
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'reason' => ['required', 'string', 'min:3', 'max:191'],
        ]);

        $movement = new StockMovementData(
            warehouseId: $data['warehouse_id'],
            productId: $data['product_id'],
            quantity: abs($data['quantity']),
            movementTypeCode: 'adjustment',
            referenceType: 'manual_adjustment',
            referenceId: null,
            userId: $request->user()?->id,
            note: $data['reason'],
        );

        try {
            $data['quantity'] > 0 ? $writer->increase($movement) : $writer->decrease($movement);
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Ajustement enregistré.'], 201);
    }

    /**
     * Retour au stock (client) : revendable → retour en stock ;
     * défectueux → retour tracé puis sortie SAV immédiate (net zéro).
     */
    public function returnIn(Request $request, StockWriterInterface $writer): JsonResponse
    {
        /** @var array{warehouse_id: int, product_id: int, quantity: int, condition: string, note?: string|null} $data */
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', 'in:resellable,defective'],
            'note' => ['nullable', 'string', 'max:191'],
        ]);

        DB::transaction(function () use ($data, $writer, $request): void {
            $note = ($data['condition'] === 'resellable' ? 'Retour client revendable' : 'Retour client défectueux')
                .(isset($data['note']) && $data['note'] !== '' ? ' — '.$data['note'] : '');

            $writer->increase(new StockMovementData(
                warehouseId: $data['warehouse_id'],
                productId: $data['product_id'],
                quantity: $data['quantity'],
                movementTypeCode: 'return_in',
                referenceType: 'customer_return',
                referenceId: null,
                userId: $request->user()?->id,
                note: $note,
            ));

            if ($data['condition'] === 'defective') {
                // L'article défectueux ne reste pas en stock vendable : sortie SAV.
                $writer->decrease(new StockMovementData(
                    warehouseId: $data['warehouse_id'],
                    productId: $data['product_id'],
                    quantity: $data['quantity'],
                    movementTypeCode: 'out',
                    referenceType: 'customer_return_defective',
                    referenceId: null,
                    userId: $request->user()?->id,
                    note: 'Sortie SAV — '.$note,
                ));
            }
        });

        return response()->json(['message' => 'Retour enregistré.'], 201);
    }
}
