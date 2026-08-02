<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\GoodsReceiptLine;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoodsReceiptDetailResource;
use App\Http\Resources\GoodsReceiptResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consultation des bons de réception (liste, détail, PDF valorisé).
 */
final class GoodsReceiptController extends Controller
{
    /**
     * Liste les bons de réception avec filtres.
     * GET /goods-receipts
     */
    public function index(Request $request): JsonResponse
    {
        $query = GoodsReceipt::query()
            ->with(['purchaseOrder', 'supplier', 'warehouse', 'createdBy'])
            ->withCount('lines')
            ->withSum('lines as total_quantity', 'quantity')
            ->addSelect([
                'total_amount' => GoodsReceiptLine::query()
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
                    ->whereColumn('goods_receipt_id', 'goods_receipts.id'),
            ]);

        // Recherche par numéro
        if ($request->filled('search')) {
            $query->where('number', 'like', '%'.$request->string('search')->value().'%');
        }

        // Filtrer par fournisseur
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        // Filtrer par lieu
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        // Filtrer par date de réception
        if ($request->filled('date_from')) {
            $query->where('received_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('received_at', '<=', $request->date('date_to'));
        }

        $receipts = $query->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => GoodsReceiptResource::collection($receipts),
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'last_page' => $receipts->lastPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
            ],
        ]);
    }

    /**
     * Détail d'un bon de réception avec lignes valorisées.
     * GET /goods-receipts/{id}
     */
    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $goodsReceipt->load(['purchaseOrder', 'supplier', 'warehouse', 'createdBy']);

        return response()->json(
            new GoodsReceiptDetailResource($goodsReceipt)
        );
    }

    /**
     * PDF du bon de réception (avec montants : PU, total ligne, Total HT).
     * GET /goods-receipts/{id}/pdf
     */
    public function pdf(GoodsReceipt $goodsReceipt): Response
    {
        $goodsReceipt->load(['purchaseOrder', 'supplier', 'warehouse']);
        $lines = $goodsReceipt->lines()
            ->with(['product', 'purchaseOrderLine'])
            ->orderBy('position')
            ->get();

        $totalAmount = round($lines->sum(fn (GoodsReceiptLine $line): float => $line->lineTotal()), 2);

        $pdf = Pdf::loadView('pdf.goods-receipt', [
            'receipt' => $goodsReceipt,
            'lines' => $lines,
            'totalAmount' => $totalAmount,
        ])->setPaper('a4');

        return $pdf->download("{$goodsReceipt->number}.pdf");
    }
}
