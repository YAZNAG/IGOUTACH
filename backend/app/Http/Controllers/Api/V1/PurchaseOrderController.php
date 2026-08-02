<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Actions\ApprovePurchaseOrderAction;
use App\Domain\Purchasing\Actions\CancelPurchaseOrderAction;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\ReceiveGoodsAction;
use App\Domain\Purchasing\Actions\SendPurchaseOrderAction;
use App\Domain\Purchasing\Actions\UpdatePurchaseOrderAction;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoodsReceiptDetailResource;
use App\Http\Resources\PurchaseOrderDetailResource;
use App\Http\Resources\PurchaseOrderResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestion des bons de commande : création, envoi, approbation, réception, annulation.
 */
final class PurchaseOrderController extends Controller
{
    /**
     * Liste les bons de commande avec filtres.
     * GET /purchase-orders
     *
     * @returns list of purchase orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'status', 'createdBy'])
            ->withCount('lines')
            ->withSum('lines as total_quantity', 'quantity')
            ->withSum('lines as total_received', 'received_quantity');

        // Recherche par numéro
        if ($request->filled('search')) {
            $query->where('number', 'like', '%'.$request->string('search')->value().'%');
        }

        // Filtrer par date
        if ($request->filled('date_from')) {
            $query->where('ordered_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('ordered_at', '<=', $request->date('date_to'));
        }

        // Filtrer par fournisseur
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        // Filtrer par statut (status_code ou status)
        $statusCode = $request->filled('status_code') ? $request->string('status_code')->value() : $request->string('status')->value();
        if ($statusCode !== '') {
            $query->byStatus($statusCode);
        }

        // Filtrer par lieu
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        $perPage = in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20;
        $orders = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'data' => PurchaseOrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Crée un nouveau bon de commande en brouillon.
     * POST /purchase-orders
     */
    public function store(Request $request, CreatePurchaseOrderAction $action): JsonResponse
    {
        /** @var array{supplier_id: int, warehouse_id: int, expected_at?: string|null, notes?: string|null, lines: list<array{product_id: int, quantity: int}>} $data */
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $order = $action->execute(
                supplierId: $data['supplier_id'],
                warehouseId: $data['warehouse_id'],
                expectedAt: isset($data['expected_at']) && $data['expected_at'] !== null ? new \DateTime($data['expected_at']) : null,
                notes: $data['notes'] ?? null,
                createdBy: $request->user()?->id ?? 0,
                lines: $data['lines'],
            );

            return response()->json(
                new PurchaseOrderDetailResource($order->load(['supplier', 'warehouse', 'status', 'createdBy', 'lines.product'])),
                201
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Affiche les détails d'un bon de commande.
     * GET /purchase-orders/{id}
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'status', 'createdBy', 'lines.product']);

        return response()->json(
            new PurchaseOrderDetailResource($purchaseOrder)
        );
    }

    /**
     * Envoie un bon de commande (brouillon → sent ou pending_approval).
     * POST /purchase-orders/{id}/send
     */
    public function send(PurchaseOrder $purchaseOrder, SendPurchaseOrderAction $action): JsonResponse
    {
        try {
            // Vérifier si l'approbation est requise (via setting ou paramètre)
            $requireApproval = (bool) \DB::table('settings')->where('key', 'purchase.require_approval')->value('value');

            $order = $action->execute($purchaseOrder, $requireApproval);
            $order->load(['supplier', 'warehouse', 'status', 'createdBy', 'lines.product']);

            return response()->json(
                new PurchaseOrderDetailResource($order)
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approuve un bon de commande (pending_approval → sent).
     * POST /purchase-orders/{id}/approve
     */
    public function approve(PurchaseOrder $purchaseOrder, ApprovePurchaseOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute($purchaseOrder);
            $order->load(['supplier', 'warehouse', 'status', 'createdBy', 'lines.product']);

            return response()->json(
                new PurchaseOrderDetailResource($order)
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Modifie un bon de commande en brouillon (remplacement intégral des lignes).
     * PUT /purchase-orders/{id}
     */
    public function update(
        Request $request,
        PurchaseOrder $purchaseOrder,
        UpdatePurchaseOrderAction $action
    ): JsonResponse {
        /** @var array{supplier_id: int, warehouse_id: int, expected_at?: string|null, notes?: string|null, lines: list<array{product_id: int, quantity: int}>} $data */
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $order = $action->execute(
                order: $purchaseOrder,
                supplierId: $data['supplier_id'],
                warehouseId: $data['warehouse_id'],
                expectedAt: isset($data['expected_at']) && $data['expected_at'] !== null ? new \DateTime($data['expected_at']) : null,
                notes: $data['notes'] ?? null,
                lines: $data['lines'],
            );
            $order->load(['supplier', 'warehouse', 'status', 'createdBy', 'lines.product']);

            return response()->json(
                new PurchaseOrderDetailResource($order)
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * PDF du bon de commande (sans aucun montant : règle métier).
     * GET /purchase-orders/{id}/pdf
     */
    public function pdf(PurchaseOrder $purchaseOrder): Response
    {
        // Le PDF n'existe que pour un bon envoyé ou plus (jamais en brouillon).
        if ($purchaseOrder->status()->first()?->code === 'draft') {
            return response()->json(['message' => 'Le PDF n\'est disponible que pour un bon de commande envoyé.'], 422);
        }

        $purchaseOrder->load(['supplier', 'warehouse', 'status']);
        $lines = $purchaseOrder->lines()->with('product.unit')->orderBy('position')->get();

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'order' => $purchaseOrder,
            'lines' => $lines,
        ])->setPaper('a4');

        return $pdf->download("{$purchaseOrder->number}.pdf");
    }

    /**
     * Réceptionne des marchandises : crée un bon de réception (BR-YYYY-0001),
     * incrémente le stock au prix réel (CMUP) et met à jour le bon de commande.
     * POST /purchase-orders/{id}/receive
     *
     * Body: { received_at, invoice_number?, notes?, lines: [{ purchase_order_line_id, quantity, unit_price, over_receipt_reason? }] }
     */
    public function receive(
        Request $request,
        PurchaseOrder $purchaseOrder,
        ReceiveGoodsAction $action
    ): JsonResponse {
        /** @var array{received_at: string, invoice_number?: string|null, notes?: string|null, lines: list<array{purchase_order_line_id: int, quantity: int, unit_price: float, over_receipt_reason?: string|null}>} $data */
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', 'exists:purchase_order_lines,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'lines.*.over_receipt_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $receipt = $action->execute(
                order: $purchaseOrder,
                receivedAt: $data['received_at'],
                invoiceNumber: $data['invoice_number'] ?? null,
                notes: $data['notes'] ?? null,
                lines: $data['lines'],
                createdBy: $request->user()?->id,
            );
            $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'createdBy']);

            return response()->json(
                new GoodsReceiptDetailResource($receipt),
                201
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Annule un bon de commande.
     * POST /purchase-orders/{id}/cancel
     */
    public function cancel(PurchaseOrder $purchaseOrder, CancelPurchaseOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute($purchaseOrder);
            $order->load(['supplier', 'warehouse', 'status', 'createdBy', 'lines.product']);

            return response()->json(
                new PurchaseOrderDetailResource($order)
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
