<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Actions\ReceivePurchaseOrderAction;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Achats : bons de commande (création, passage en commande, réception
 * partielle/totale avec reliquats), retours fournisseur et suggestions
 * de réapprovisionnement.
 */
final class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier:id,code,name', 'warehouse:id,code'])
            ->withCount('lines')
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderByDesc('id')
            ->paginate(20);

        $orders->through(fn (PurchaseOrder $o): array => [
            'id' => $o->id,
            'reference' => $o->reference,
            'supplier' => $o->supplier?->name,
            'warehouse' => $o->warehouse?->code,
            'status' => $o->status,
            'expected_at' => $o->expected_at?->format('Y-m-d'),
            'lines_count' => $o->lines_count,
            'created_at' => $o->created_at?->format('Y-m-d'),
        ]);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(Request $request, DocumentNumberGeneratorInterface $numbers): JsonResponse
    {
        /** @var array{supplier_id: int, warehouse_id: int, expected_at?: string|null, note?: string|null, lines: list<array{product_id: int, quantity: int, unit_price: float}>} $data */
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'expected_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data, $numbers, $request): PurchaseOrder {
            $order = PurchaseOrder::query()->create([
                'reference' => $numbers->next('purchase'),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => PurchaseOrder::STATUS_ORDERED,
                'expected_at' => $data['expected_at'] ?? null,
                'created_by' => $request->user()?->id,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $order->lines()->create([
                    'product_id' => $line['product_id'],
                    'quantity_ordered' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);
            }

            return $order;
        });

        return response()->json(['data' => ['id' => $order->id, 'reference' => $order->reference]], 201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier:id,code,name', 'warehouse:id,code,name', 'lines.product:id,sku,name']);

        return response()->json(['data' => [
            'id' => $purchaseOrder->id,
            'reference' => $purchaseOrder->reference,
            'supplier' => $purchaseOrder->supplier?->name,
            'warehouse' => $purchaseOrder->warehouse?->code,
            'status' => $purchaseOrder->status,
            'expected_at' => $purchaseOrder->expected_at?->format('Y-m-d'),
            'note' => $purchaseOrder->note,
            'lines' => $purchaseOrder->lines->map(fn ($l): array => [
                'id' => $l->id,
                'sku' => $l->product?->sku,
                'name' => $l->product?->name,
                'quantity_ordered' => $l->quantity_ordered,
                'quantity_received' => $l->quantity_received,
                'remaining' => $l->remaining(),
                'unit_price' => (float) $l->unit_price,
            ])->values()->all(),
        ]]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, ReceivePurchaseOrderAction $action): JsonResponse
    {
        /** @var array{quantities: array<int|string, int>} $data */
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['integer', 'min:0'],
        ]);

        $quantities = [];
        foreach ($data['quantities'] as $lineId => $qty) {
            $quantities[(int) $lineId] = (int) $qty;
        }

        try {
            $action->execute($purchaseOrder, $quantities, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($purchaseOrder->refresh());
    }

    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (! in_array($purchaseOrder->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_ORDERED], true)) {
            return response()->json(['message' => 'Seule une commande non réceptionnée peut être annulée.'], 422);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return $this->show($purchaseOrder->refresh());
    }

    /**
     * Retour fournisseur : sortie de stock tracée (mouvement return_out).
     */
    public function supplierReturn(Request $request, StockWriterInterface $stock): JsonResponse
    {
        /** @var array{supplier_id: int, warehouse_id: int, note?: string|null, lines: list<array{product_id: int, quantity: int}>} $data */
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($data, $stock, $request): void {
                foreach ($data['lines'] as $line) {
                    $stock->decrease(new StockMovementData(
                        warehouseId: $data['warehouse_id'],
                        productId: $line['product_id'],
                        quantity: $line['quantity'],
                        movementTypeCode: 'return_out',
                        referenceType: 'supplier_return',
                        referenceId: $data['supplier_id'],
                        userId: $request->user()?->id,
                        note: $data['note'] ?? 'Retour fournisseur',
                    ));
                }
            });
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Retour fournisseur enregistré.'], 201);
    }

    /**
     * Suggestions de réapprovisionnement : articles sous leur seuil minimal
     * (stock total tous lieux < seuil).
     */
    public function replenishment(): JsonResponse
    {
        $rows = DB::table('products')
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->whereNotNull('products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.min_stock')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) < products.min_stock')
            ->orderBy('products.name')
            ->get([
                'products.id as product_id',
                'products.sku',
                'products.name',
                'products.min_stock',
                DB::raw('COALESCE(SUM(stocks.quantity), 0) as current_stock'),
            ]);

        return response()->json(['data' => $rows]);
    }
}
