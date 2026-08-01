<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Stock\Actions\ApproveInventoryAction;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveInventoryLinesRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Resources\InventoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

final class InventoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $inventories = Inventory::query()
            ->with('warehouse:id,code,name')
            ->withCount('lines')
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->orderByDesc('id')
            ->paginate(20);

        return InventoryResource::collection($inventories);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        /** @var array{warehouse_id: int, counted_at: string, note?: string|null} $data */
        $data = $request->validated();

        $inventory = Inventory::query()->create([
            'reference' => 'INV-'.now()->format('ymdHis'),
            'warehouse_id' => $data['warehouse_id'],
            'counted_at' => $data['counted_at'],
            'status' => Inventory::STATUS_DRAFT,
            'created_by' => $request->user()?->id,
            'note' => $data['note'] ?? null,
        ]);

        return InventoryResource::make($inventory->load('warehouse'))->response()->setStatusCode(201);
    }

    public function show(Inventory $inventory): InventoryResource
    {
        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name']));
    }

    /**
     * Enregistre les quantités comptées : calcule le théorique et l'écart.
     */
    public function saveLines(SaveInventoryLinesRequest $request, Inventory $inventory, StockReaderInterface $reader): JsonResponse|InventoryResource
    {
        if ($inventory->status !== Inventory::STATUS_DRAFT) {
            return response()->json(['message' => 'Seul un inventaire en brouillon est modifiable.'], 422);
        }

        /** @var array{lines: list<array{product_id: int, counted_quantity: int}>} $data */
        $data = $request->validated();

        $inventory->lines()->delete();

        foreach ($data['lines'] as $line) {
            $system = $reader->quantityFor($inventory->warehouse_id, $line['product_id']);
            $inventory->lines()->create([
                'product_id' => $line['product_id'],
                'counted_quantity' => $line['counted_quantity'],
                'system_quantity' => $system,
                'difference' => $line['counted_quantity'] - $system,
            ]);
        }

        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name']));
    }

    public function cancel(Inventory $inventory): JsonResponse|InventoryResource
    {
        if ($inventory->status !== Inventory::STATUS_DRAFT) {
            return response()->json(['message' => 'Seul un inventaire en brouillon peut être annulé.'], 422);
        }

        $inventory->update(['status' => Inventory::STATUS_CANCELLED]);

        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name']));
    }

    public function approve(Inventory $inventory, ApproveInventoryAction $action, Request $request): JsonResponse|InventoryResource
    {
        try {
            $action->execute($inventory, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name']));
    }
}
