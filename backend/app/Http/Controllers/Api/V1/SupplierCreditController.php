<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\Actions\DeclareChequeAction;
use App\Domain\Payments\Models\Cheque;
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
     * Journal de tous les règlements fournisseurs, tous fournisseurs confondus.
     * GET /supplier-payments?supplier_id=&date_from=&date_to=&payment_method_id=
     *
     * L'historique par fournisseur existe déjà, mais il oblige à ouvrir un
     * fournisseur à la fois : impossible de répondre à « qu'a-t-on payé cette
     * semaine ». Ce journal couvre la période, pas un tiers.
     */
    public function paymentsJournal(Request $request): JsonResponse
    {
        $filtres = fn ($q) => $q
            ->when($request->integer('supplier_id') > 0, fn ($x) => $x->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->integer('payment_method_id') > 0, fn ($x) => $x->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->string('date_from')->isNotEmpty(), fn ($x) => $x->whereDate('paid_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($x) => $x->whereDate('paid_at', '<=', $request->string('date_to')->value()));

        $paiements = $filtres(SupplierPayment::query()
            ->with([
                'paymentMethod:id,name',
                'createdBy:id,name',
                'goodsReceipt:id,number',
                'supplier:id,code,name',
            ]))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(fn (SupplierPayment $p): array => [
                'id' => $p->id,
                'supplier' => $p->supplier?->name,
                'supplier_id' => $p->supplier_id,
                'goods_receipt' => $p->goodsReceipt?->number,
                'goods_receipt_id' => $p->goods_receipt_id,
                'amount' => (float) $p->amount,
                'paid_at' => $p->paid_at->format('Y-m-d'),
                'created_at' => $p->created_at?->format('Y-m-d H:i'),
                'payment_method' => $p->paymentMethod?->name,
                'notes' => $p->notes,
                'created_by' => $p->createdBy?->name,
            ]);

        // Le total porte sur toute la période filtrée, pas sur les 300 lignes
        // affichées : autrement il annoncerait moins que la réalité.
        $totaux = $filtres(SupplierPayment::query())
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(amount), 0) as montant')
            ->first();

        return response()->json(['data' => [
            'rows' => $paiements->all(),
            'count' => (int) ($totaux?->getAttribute('nb') ?? 0),
            'total_paid' => round((float) ($totaux?->getAttribute('montant') ?? 0), 2),
            'truncated' => (int) ($totaux?->getAttribute('nb') ?? 0) > $paiements->count(),
        ]]);
    }

    /**
     * Historique de tous les règlements d'un fournisseur.
     * GET /suppliers/{supplierId}/payments
     */
    public function supplierPayments(int $supplierId): JsonResponse
    {
        $payments = SupplierPayment::query()
            ->with(['paymentMethod:id,name', 'createdBy:id,name', 'goodsReceipt:id,number'])
            ->where('supplier_id', $supplierId)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SupplierPayment $payment): array => [
                'id' => $payment->id,
                'goods_receipt' => $payment->goodsReceipt !== null ? [
                    'id' => $payment->goodsReceipt->id,
                    'number' => $payment->goodsReceipt->number,
                ] : null,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at->format('Y-m-d'),
                'created_at' => $payment->created_at?->format('Y-m-d H:i'),
                'payment_method' => $payment->paymentMethod?->name,
                'notes' => $payment->notes,
                'created_by' => $payment->createdBy?->name,
            ]);

        return response()->json(['data' => [
            'rows' => $payments->all(),
            'total_paid' => round($payments->sum('amount'), 2),
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
                'created_at' => $payment->created_at?->format('Y-m-d H:i'),
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
    public function pay(
        Request $request,
        GoodsReceipt $goodsReceipt,
        PaySupplierCreditAction $action,
        DeclareChequeAction $declarer,
    ): JsonResponse {
        /** @var array{amount: float, payment_method_id?: int|null, paid_at?: string|null, notes?: string|null, cheque_id?: int|null, cheque?: array{instrument?: string|null, number: string, cheque_date: string, bank?: string|null, origin: string, drawer_name?: string|null}} $data */
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'cheque_id' => ['nullable', 'integer', 'exists:cheques,id'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Effet remis au fournisseur : le nôtre, ou celui d'un tiers que
            // l'on endosse à son profit.
            ...DeclareChequeAction::reglesImbriquees([
                Cheque::ORIGIN_OWN,
                Cheque::ORIGIN_THIRD_PARTY,
            ]),
        ]);

        if (isset($data['cheque'])) {
            try {
                $cheque = $declarer->execute(
                    donnees: $data['cheque'],
                    direction: Cheque::DIRECTION_OUT,
                    montant: (float) $data['amount'],
                    supplierId: (int) $goodsReceipt->supplier_id,
                    createdBy: $request->user()?->id,
                );
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $data['cheque_id'] = $cheque->id;
        }

        try {
            $payment = $action->execute(
                receipt: $goodsReceipt,
                amount: (float) $data['amount'],
                paymentMethodId: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                paidAt: $data['paid_at'] ?? now()->format('Y-m-d'),
                notes: $data['notes'] ?? null,
                createdBy: $request->user()?->id,
                chequeId: isset($data['cheque_id']) ? (int) $data['cheque_id'] : null,
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
