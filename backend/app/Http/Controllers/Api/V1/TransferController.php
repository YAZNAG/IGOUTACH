<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Services\ProductCostResolver;
use App\Domain\Stock\Actions\CreateTransferAction;
use App\Domain\Stock\Actions\ReceiveTransferAction;
use App\Domain\Stock\DTOs\TransferData;
use App\Domain\Stock\DTOs\TransferLineData;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Domain\Stock\Exceptions\InvalidTransferException;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Transferts inter-lieux : liste (avec alerte transit > 3 jours),
 * création (envoi) et réception avec saisie des écarts.
 */
final class TransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transfers = Transfer::query()
            ->with(['fromWarehouse:id,code', 'toWarehouse:id,code', 'status:id,code,name'])
            ->withCount('lines')
            ->when($request->integer('warehouse_id') > 0, function ($q) use ($request) {
                $id = $request->integer('warehouse_id');
                $q->where(fn ($sub) => $sub->where('from_warehouse_id', $id)->orWhere('to_warehouse_id', $id));
            })
            ->orderByDesc('id')
            ->paginate(20);

        $transfers->through(function (Transfer $t): array {
            $inTransitDays = $t->status?->code === TransferStatus::IN_TRANSIT && $t->sent_at !== null
                ? (int) $t->sent_at->diffInDays(now())
                : null;

            return [
                'id' => $t->id,
                'reference' => $t->reference,
                'from' => $t->fromWarehouse?->code,
                'to' => $t->toWarehouse?->code,
                'status' => $t->status?->code,
                'status_name' => $t->status?->name,
                'lines_count' => $t->lines_count,
                'sent_at' => $t->sent_at?->format('Y-m-d H:i'),
                'received_at' => $t->received_at?->format('Y-m-d H:i'),
                'days_in_transit' => $inTransitDays,
                'is_late' => $inTransitDays !== null && $inTransitDays > 3,
            ];
        });

        return response()->json([
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function store(Request $request, CreateTransferAction $action, ProductCostResolver $cost): JsonResponse
    {
        /** @var array{from_warehouse_id: int, to_warehouse_id: int, note?: string|null, lines: list<array{product_id: int, quantity: int}>} $data */
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Le CMUP du produit voyage avec la marchandise.
        $lines = array_map(function (array $line) use ($cost): TransferLineData {
            /** @var Product $product */
            $product = Product::query()->findOrFail($line['product_id']);

            return new TransferLineData(
                productId: $line['product_id'],
                quantity: $line['quantity'],
                unitCost: $cost->unitCost($product),
            );
        }, $data['lines']);

        try {
            $transfer = $action->execute(new TransferData(
                fromWarehouseId: $data['from_warehouse_id'],
                toWarehouseId: $data['to_warehouse_id'],
                lines: $lines,
                userId: $request->user()?->id,
                note: $data['note'] ?? null,
            ));
        } catch (InvalidTransferException|InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $transfer->id, 'reference' => $transfer->reference]], 201);
    }

    public function show(Transfer $transfer): JsonResponse
    {
        $transfer->load(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name', 'status:id,code,name', 'lines.product:id,sku,name']);

        return response()->json(['data' => [
            'id' => $transfer->id,
            'reference' => $transfer->reference,
            'from' => $transfer->fromWarehouse?->code,
            'to' => $transfer->toWarehouse?->code,
            'status' => $transfer->status?->code,
            'sent_at' => $transfer->sent_at?->format('Y-m-d H:i'),
            'received_at' => $transfer->received_at?->format('Y-m-d H:i'),
            'note' => $transfer->note,
            'lines' => $transfer->lines->map(fn ($l): array => [
                'id' => $l->id,
                'sku' => $l->product?->sku,
                'name' => $l->product?->name,
                'quantity_sent' => $l->quantity_sent,
                'quantity_received' => $l->quantity_received,
            ])->values()->all(),
        ]]);
    }

    public function receive(Request $request, Transfer $transfer, ReceiveTransferAction $action): JsonResponse
    {
        /** @var array{quantities?: array<int|string, int>} $data */
        $data = $request->validate([
            'quantities' => ['sometimes', 'array'],
            'quantities.*' => ['integer', 'min:0'],
        ]);

        $quantities = [];
        foreach ($data['quantities'] ?? [] as $lineId => $qty) {
            $quantities[(int) $lineId] = (int) $qty;
        }

        try {
            $action->execute($transfer, $quantities, $request->user()?->id);
        } catch (InvalidTransferException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($transfer->refresh());
    }
}
