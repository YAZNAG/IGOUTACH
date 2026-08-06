<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Encaissements clients, cycle des chèques, balance âgée et relevé client.
 */
final class PaymentController extends Controller
{
    /**
     * Vue globale = voit les clients et règlements de tous les lieux.
     */
    private function hasGlobalView(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && ($user->can('customer.view_all') || $user->can('stock.view_global'));
    }

    /**
     * Clients visibles par l'utilisateur : ceux qu'il a créés.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Customer>
     */
    private function visibleCustomerIds(Request $request)
    {
        return Customer::query()->select('id')->where('created_by', $request->user()?->id);
    }

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with(['customer:id,code,name', 'method:id,name'])
            ->when($request->integer('customer_id') > 0, fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            // Cloisonnement : sans vue globale, on ne voit que les règlements
            // de ses propres clients (les paiements n'ont pas de lieu propre).
            ->when(! $this->hasGlobalView($request), fn ($q) => $q->whereIn('customer_id', $this->visibleCustomerIds($request)))
            ->orderByDesc('id')
            ->paginate(20);

        $payments->through(fn (Payment $p): array => [
            'id' => $p->id,
            'reference' => $p->reference,
            'customer' => $p->customer?->name,
            'method' => $p->method?->name,
            'amount' => (float) $p->amount,
            'cheque_status' => $p->cheque_status,
            'cheque_reference' => $p->cheque_reference,
            'received_at' => $p->received_at->format('Y-m-d'),
        ]);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function store(Request $request, RecordPaymentAction $action): JsonResponse
    {
        /** @var array{customer_id: int, amount: float, payment_method_id?: int|null, sale_id?: int|null, cash_session_id?: int|null, cheque_reference?: string|null, received_at: string, note?: string|null} $data */
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'cash_session_id' => ['nullable', 'integer', 'exists:cash_sessions,id'],
            'cheque_reference' => ['nullable', 'string', 'max:60'],
            'received_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payment = $action->execute($data, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $payment->id, 'reference' => $payment->reference]], 201);
    }

    /**
     * Cycle du chèque : received → deposited → cleared | bounced.
     */
    public function chequeStatus(Request $request, Payment $payment): JsonResponse
    {
        /** @var array{status: string} $data */
        $data = $request->validate([
            'status' => ['required', 'in:deposited,cleared,bounced'],
        ]);

        if ($payment->cheque_status === null) {
            return response()->json(['message' => "Cet encaissement n'est pas un chèque."], 422);
        }

        $payment->update(['cheque_status' => $data['status']]);

        return response()->json(['data' => ['id' => $payment->id, 'cheque_status' => $payment->cheque_status]]);
    }

    /**
     * Balance âgée : encours par tranches d'ancienneté des factures impayées.
     */
    public function aging(): JsonResponse
    {
        $rows = Sale::query()
            ->with('customer:id,code,name')
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('payment_status', '!=', 'paid')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($sales) {
                $first = $sales->first();
                $buckets = ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'over60' => 0.0];

                foreach ($sales as $sale) {
                    $due = (float) $sale->total - (float) $sale->paid_amount;
                    $age = $sale->confirmed_at !== null ? (int) $sale->confirmed_at->diffInDays(now()) : 0;
                    $key = $age <= 30 ? 'current' : ($age <= 60 ? 'b30' : ($age <= 90 ? 'b60' : 'over60'));
                    $buckets[$key] += $due;
                }

                return [
                    'customer_id' => $first?->customer?->id,
                    'customer' => $first?->customer?->name,
                    'bucket_0_30' => round($buckets['current'], 2),
                    'bucket_31_60' => round($buckets['b30'], 2),
                    'bucket_61_90' => round($buckets['b60'], 2),
                    'bucket_over_90' => round($buckets['over60'], 2),
                    'total_due' => round($buckets['current'] + $buckets['b30'] + $buckets['b60'] + $buckets['over60'], 2),
                ];
            })
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * Relevé client : écritures du grand-livre, plus récentes en premier.
     */
    public function statement(Request $request, Customer $customer): JsonResponse
    {
        // Un utilisateur sans vue globale ne consulte que ses propres clients.
        if (! $this->hasGlobalView($request)
            && $customer->created_by !== null
            && $customer->created_by !== $request->user()?->id) {
            abort(403, 'Ce client a été créé par un autre utilisateur.');
        }

        $entries = CustomerLedgerEntry::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (CustomerLedgerEntry $e): array => [
                'date' => $e->created_at?->format('Y-m-d H:i'),
                'type' => $e->type,
                'amount' => (float) $e->amount,
                'balance_after' => (float) $e->balance_after,
                'note' => $e->note,
            ]);

        return response()->json(['data' => [
            'customer' => ['id' => $customer->id, 'code' => $customer->code, 'name' => $customer->name],
            'balance' => (float) $customer->balance,
            'credit_limit' => (float) $customer->credit_limit,
            'is_blocked' => $customer->is_blocked,
            'entries' => $entries->values()->all(),
        ]]);
    }
}
