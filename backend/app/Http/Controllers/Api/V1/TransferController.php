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
use App\Rules\WarehouseAccessible;
use Illuminate\Support\Facades\DB;
use App\Support\Scopes\WarehouseScope;

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
            // Cloisonnement : sans vue multi-lieux, on ne voit que les
            // transferts qui partent de son lieu ou qui lui sont destines.
            // Le modele ne porte pas de scope global : un transfert a deux
            // lieux, un filtre sur une seule colonne serait faux.
            ->when(
                ! ($request->user()?->can(WarehouseScope::DEFAULT_GLOBAL_PERMISSION) ?? false),
                function ($q) use ($request) {
                    $sien = $request->user()?->getAttribute('warehouse_id');
                    $q->where(fn ($sub) => $sub->where('from_warehouse_id', $sien)
                        ->orWhere('to_warehouse_id', $sien));
                },
            )
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

    /**
     * Demande de transfert vers son propre lieu.
     *
     * Aucune marchandise ne bouge : la demande attend un accord. Sans cela,
     * le stock du lieu source diminuerait sur simple demande.
     */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id', new WarehouseAccessible],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $statut = TransferStatus::query()->where('code', TransferStatus::REQUESTED)->firstOrFail();

        $transfer = DB::transaction(function () use ($data, $request, $statut): Transfer {
            $transfer = Transfer::withoutGlobalScopes()->create([
                'reference' => 'DT-'.now()->format('ymdHis'),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'transfer_status_id' => $statut->id,
                'created_by' => $request->user()?->id,
                'requested_by' => $request->user()?->id,
                'requested_at' => now(),
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['lines'] as $ligne) {
                $transfer->lines()->create([
                    'product_id' => $ligne['product_id'],
                    'quantity_sent' => $ligne['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => 0,
                ]);
            }

            return $transfer;
        });

        return response()->json([
            'data' => ['id' => $transfer->id, 'reference' => $transfer->reference, 'status' => TransferStatus::REQUESTED],
        ], 201);
    }

    /**
     * Qui peut accorder ou refuser une demande.
     *
     * La direction (vue multi-lieux) arbitre partout. A defaut, c'est le
     * responsable du lieu SOURCE : c'est son stock qui part, la decision lui
     * revient. Le demandeur ne peut pas s'auto-servir.
     */
    private function peutArbitrer(Request $request, Transfer $transfer): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($user->can(WarehouseScope::DEFAULT_GLOBAL_PERMISSION)) {
            return true;
        }

        return (int) $user->getAttribute('warehouse_id') === (int) $transfer->from_warehouse_id;
    }

    /**
     * Accord de la direction : la demande devient un transfert réel et le
     * stock quitte enfin le lieu source.
     */
    public function approve(Request $request, Transfer $transfer, CreateTransferAction $action, ProductCostResolver $cost): JsonResponse
    {
        // Quantites ajustables a l'acceptation : celui qui donne sait ce qu'il
        // peut reellement ceder, et n'a pas a refuser la demande entiere pour
        // en corriger une ligne.
        $data = $request->validate([
            'lines' => ['sometimes', 'array'],
            'lines.*.product_id' => ['required_with:lines', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required_with:lines', 'integer', 'min:0'],
        ]);

        $verrou = Transfer::withoutGlobalScopes()->lockForUpdate()->find($transfer->id);

        if ($verrou === null || $verrou->status?->code !== TransferStatus::REQUESTED) {
            return response()->json(['message' => 'Seule une demande en attente peut être approuvée.'], 422);
        }

        if (! $this->peutArbitrer($request, $verrou)) {
            return response()->json([
                'message' => 'Seul le responsable du lieu qui fournit, ou la direction, peut traiter cette demande.',
            ], 403);
        }

        $ajustees = [];

        foreach ($data['lines'] ?? [] as $ligne) {
            $ajustees[(int) $ligne['product_id']] = (int) $ligne['quantity'];
        }

        $lignes = [];

        foreach ($verrou->lines()->with('product')->get() as $l) {
            $quantite = $ajustees[(int) $l->product_id] ?? (int) $l->quantity_sent;

            // Une ligne ramenee a zero est retiree : envoyer zero unite
            // creerait un mouvement vide dans l'historique.
            if ($quantite <= 0) {
                continue;
            }

            // La demande garde trace de ce qui a ete reellement accorde.
            if ($quantite !== (int) $l->quantity_sent) {
                $l->update(['quantity_sent' => $quantite]);
            }

            $lignes[] = new TransferLineData(
                productId: (int) $l->product_id,
                quantity: $quantite,
                unitCost: $cost->unitCost($l->product),
            );
        }

        if ($lignes === []) {
            return response()->json([
                'message' => 'Toutes les lignes sont à zéro : refusez la demande plutôt que de l’approuver à vide.',
            ], 422);
        }

        try {
            $reel = $action->execute(new TransferData(
                fromWarehouseId: (int) $verrou->from_warehouse_id,
                toWarehouseId: (int) $verrou->to_warehouse_id,
                lines: $lignes,
                userId: $request->user()?->id,
                note: $verrou->note,
            ));
        } catch (InvalidTransferException|InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // La demande porte la trace de l'accord et cède la place au transfert
        // réellement exécuté, seul porteur des mouvements de stock.
        $reel->update([
            'requested_by' => $verrou->requested_by,
            'requested_at' => $verrou->requested_at,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        $verrou->lines()->delete();
        $verrou->delete();

        return response()->json(['data' => ['id' => $reel->id, 'reference' => $reel->reference, 'status' => TransferStatus::IN_TRANSIT]]);
    }

    public function refuse(Request $request, Transfer $transfer): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        if ($transfer->status?->code !== TransferStatus::REQUESTED) {
            return response()->json(['message' => 'Seule une demande en attente peut être refusée.'], 422);
        }

        if (! $this->peutArbitrer($request, $transfer)) {
            return response()->json([
                'message' => 'Seul le responsable du lieu qui fournit, ou la direction, peut traiter cette demande.',
            ], 403);
        }

        $statut = TransferStatus::query()->where('code', TransferStatus::REFUSED)->firstOrFail();

        $transfer->update([
            'transfer_status_id' => $statut->id,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
            'refusal_reason' => $data['reason'] ?? null,
        ]);

        return response()->json(['data' => ['id' => $transfer->id, 'status' => TransferStatus::REFUSED]]);
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
