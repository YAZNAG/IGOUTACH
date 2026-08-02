<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Actions\PaySupplierCreditAction;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\SupplierPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Crédits fournisseurs : bons de réception non entièrement réglés,
 * synthèse par fournisseur et enregistrement des règlements.
 */
final class SupplierCreditController extends Controller
{
    /**
     * Liste des crédits en cours (reste à payer > 0).
     * GET /supplier-credits?supplier_id=&search=
     */
    public function index(Request $request): JsonResponse
    {
        $totalSub = DB::table('goods_receipt_lines')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
            ->whereColumn('goods_receipt_id', 'goods_receipts.id');

        $query = GoodsReceipt::query()
            ->with(['supplier:id,code,name', 'warehouse:id,code,name', 'purchaseOrder:id,number'])
            ->select('goods_receipts.*')
            ->selectSub($totalSub, 'total_amount')
            ->whereRaw('(SELECT COALESCE(SUM(quantity * unit_price), 0) FROM goods_receipt_lines WHERE goods_receipt_id = goods_receipts.id) - amount_paid > 0.005')
            ->when($request->integer('supplier_id') > 0, fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('number', 'like', '%'.$request->string('search')->value().'%'))
            ->orderBy('received_at');

        $receipts = $query->get();

        $rows = $receipts->map(function (GoodsReceipt $receipt): array {
            $total = (float) $receipt->getAttribute('total_amount');
            $paid = (float) $receipt->amount_paid;

            return [
                'id' => $receipt->id,
                'number' => $receipt->number,
                'purchase_order' => $receipt->purchaseOrder !== null ? [
                    'id' => $receipt->purchaseOrder->id,
                    'number' => $receipt->purchaseOrder->number,
                ] : null,
                'supplier' => [
                    'id' => $receipt->supplier?->id,
                    'name' => $receipt->supplier?->name,
                    'code' => $receipt->supplier?->code,
                ],
                'warehouse' => [
                    'id' => $receipt->warehouse?->id,
                    'name' => $receipt->warehouse?->name,
                    'code' => $receipt->warehouse?->code,
                ],
                'received_at' => $receipt->received_at?->format('Y-m-d'),
                'invoice_number' => $receipt->invoice_number,
                'payment_status' => $receipt->payment_status,
                'total_amount' => round($total, 2),
                'amount_paid' => round($paid, 2),
                'remaining_amount' => round(max(0, $total - $paid), 2),
            ];
        })->values();

        // Synthèse par fournisseur pour les cartes de tête.
        $bySupplier = $rows->groupBy(fn (array $row) => $row['supplier']['id'])
            ->map(fn ($group) => [
                'supplier' => $group->first()['supplier'],
                'receipts_count' => $group->count(),
                'total_due' => round($group->sum('remaining_amount'), 2),
            ])
            ->sortByDesc('total_due')
            ->values();

        return response()->json(['data' => [
            'rows' => $rows->all(),
            'suppliers' => $bySupplier->all(),
            'total_due' => round($rows->sum('remaining_amount'), 2),
            'receipts_count' => $rows->count(),
        ]]);
    }

    /**
     * Historique des règlements d'un bon de réception.
     * GET /goods-receipts/{id}/payments
     */
    public function payments(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $payments = SupplierPayment::query()
            ->with(['paymentMethod:id,name', 'createdBy:id,name'])
            ->where('goods_receipt_id', $goodsReceipt->id)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SupplierPayment $payment): array => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at->format('Y-m-d'),
                'payment_method' => $payment->paymentMethod?->name,
                'notes' => $payment->notes,
                'created_by' => $payment->createdBy?->name,
            ]);

        return response()->json(['data' => $payments->all()]);
    }

    /**
     * Enregistre un règlement (total ou partiel) sur un bon de réception.
     * POST /goods-receipts/{id}/pay
     */
    public function pay(Request $request, GoodsReceipt $goodsReceipt, PaySupplierCreditAction $action): JsonResponse
    {
        /** @var array{amount: float, payment_method_id?: int|null, paid_at?: string|null, notes?: string|null} $data */
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $action->execute(
                receipt: $goodsReceipt,
                amount: (float) $data['amount'],
                paymentMethodId: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                paidAt: $data['paid_at'] ?? now()->format('Y-m-d'),
                notes: $data['notes'] ?? null,
                createdBy: $request->user()?->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $goodsReceipt->refresh()->load('lines');

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'payment_status' => $goodsReceipt->payment_status,
                'amount_paid' => round((float) $goodsReceipt->amount_paid, 2),
                'remaining_amount' => $goodsReceipt->remainingAmount(),
            ],
        ], 201);
    }
}
