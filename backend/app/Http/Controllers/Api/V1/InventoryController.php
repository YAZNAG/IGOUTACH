<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Stock\Actions\ApproveInventoryAction;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\Inventory;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveInventoryLinesRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Support\Export\HtmlTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
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
        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name,cost_price']));
    }

    /**
     * Feuille de comptage (sans le stock théorique, pour compter sans recopier) :
     * tous les articles du lieu avec une colonne « Compté » vide.
     */
    public function sheet(Request $request, Inventory $inventory): mixed
    {
        $rows = DB::table('products')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['sku', 'name'])
            ->map(fn (\stdClass $row): array => [$row->sku, $row->name, ''])
            ->values()
            ->all();

        $headings = ['Référence', 'Article', 'Quantité comptée'];
        $warehouseCode = $inventory->warehouse !== null ? $inventory->warehouse->code : '';
        $title = 'Feuille de comptage '.$inventory->reference.' — '.$warehouseCode;

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(
                HtmlTable::render($title, $headings, $rows),
            )->download('comptage-'.$inventory->reference.'.pdf');
        }

        return Excel::download(
            new ArrayExport($headings, $rows),
            'comptage-'.$inventory->reference.'.xlsx',
        );
    }

    /**
     * Enregistre les quantités comptées : calcule le théorique et l'écart.
     */
    public function saveLines(SaveInventoryLinesRequest $request, Inventory $inventory, StockReaderInterface $reader): JsonResponse|InventoryResource
    {
        if ($inventory->status !== Inventory::STATUS_DRAFT) {
            return response()->json(['message' => 'Seul un inventaire en brouillon est modifiable.'], 422);
        }

        /** @var array{lines: list<array{product_id: int, counted_quantity: int, reason?: string|null}>} $data */
        $data = $request->validated();

        // Motif obligatoire pour toute ligne en écart avec le théorique.
        foreach ($data['lines'] as $line) {
            $system = $reader->quantityFor($inventory->warehouse_id, $line['product_id']);
            if ($line['counted_quantity'] !== $system && trim((string) ($line['reason'] ?? '')) === '') {
                return response()->json([
                    'message' => "Motif d'écart obligatoire pour l'article #{$line['product_id']} (compté {$line['counted_quantity']}, théorique {$system}).",
                ], 422);
            }
        }

        // Enregistrement incrémental : on met à jour les articles envoyés et on
        // conserve les comptages déjà saisis (comptage étalé sur plusieurs jours).
        foreach ($data['lines'] as $line) {
            $system = $reader->quantityFor($inventory->warehouse_id, $line['product_id']);
            $inventory->lines()->updateOrCreate(
                ['product_id' => $line['product_id']],
                [
                    'counted_quantity' => $line['counted_quantity'],
                    'system_quantity' => $system,
                    'difference' => $line['counted_quantity'] - $system,
                    'reason' => $line['reason'] ?? null,
                ],
            );
        }

        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name,cost_price']));
    }

    /**
     * Met à jour l'en-tête d'un brouillon : date de comptage (reprise un autre
     * jour) et note. Le lieu reste figé, les comptages y sont rattachés.
     */
    public function update(Request $request, Inventory $inventory): JsonResponse|InventoryResource
    {
        if ($inventory->status !== Inventory::STATUS_DRAFT) {
            return response()->json(['message' => 'Seul un inventaire en brouillon est modifiable.'], 422);
        }

        /** @var array{counted_at?: string, note?: string|null} $data */
        $data = $request->validate([
            'counted_at' => ['sometimes', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $inventory->update($data);

        return InventoryResource::make($inventory->refresh()->load(['warehouse', 'lines.product:id,sku,name,cost_price']));
    }

    /**
     * Retire un comptage saisi par erreur : l'article redevient « non compté »
     * et ne sera donc pas régularisé à la validation.
     */
    public function removeLine(Inventory $inventory, int $productId): JsonResponse|InventoryResource
    {
        if ($inventory->status !== Inventory::STATUS_DRAFT) {
            return response()->json(['message' => 'Seul un inventaire en brouillon est modifiable.'], 422);
        }

        $inventory->lines()->where('product_id', $productId)->delete();

        return InventoryResource::make($inventory->load(['warehouse', 'lines.product:id,sku,name,cost_price']));
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
