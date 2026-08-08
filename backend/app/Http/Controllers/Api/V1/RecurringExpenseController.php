<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Expenses\Models\RecurringExpense;
use App\Domain\Expenses\Models\RecurringExpenseOccurrence;
use App\Domain\Expenses\Services\RecurringExpenseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Charges fixes et leurs échéances mensuelles.
 */
final class RecurringExpenseController extends Controller
{
    public function __construct(private readonly RecurringExpenseService $service) {}

    public function index(Request $request): JsonResponse
    {
        // Les échéances manquantes sont créées à la consultation : la charge
        // apparaît ainsi dès le mois venu, sans dépendre d'une tâche planifiée
        // qui peut ne pas tourner sur un hébergement mutualisé.
        $this->service->genererEcheances();

        $charges = RecurringExpense::query()
            ->with(['category:id,name', 'warehouse:id,code,name'])
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('label')
            ->get();

        return response()->json(['data' => $charges->map(fn (RecurringExpense $c) => $this->formatCharge($c))->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->valider($request);
        $data['created_by'] = $request->user()?->id;

        $charge = RecurringExpense::query()->create($data);
        $this->service->genererEcheances();

        return response()->json([
            'data' => $this->formatCharge($charge->load(['category:id,name', 'warehouse:id,code,name'])),
        ], 201);
    }

    public function update(Request $request, RecurringExpense $recurringExpense): JsonResponse
    {
        $recurringExpense->update($this->valider($request));
        $this->service->genererEcheances();

        return response()->json([
            'data' => $this->formatCharge($recurringExpense->fresh()->load(['category:id,name', 'warehouse:id,code,name'])),
        ]);
    }

    public function destroy(RecurringExpense $recurringExpense): JsonResponse
    {
        $regles = $recurringExpense->occurrences()
            ->where('status', RecurringExpenseOccurrence::STATUS_PAID)->count();

        // Supprimer une charge déjà réglée effacerait des paiements de
        // l'historique. On la désactive : elle cesse de générer des échéances
        // sans effacer ce qui a été payé.
        if ($regles > 0) {
            $recurringExpense->update(['is_active' => false]);

            return response()->json([
                'message' => "Cette charge compte {$regles} échéance(s) déjà réglée(s) : elle a été désactivée plutôt que supprimée.",
                'data' => $this->formatCharge($recurringExpense->fresh()),
            ]);
        }

        $recurringExpense->delete();

        return response()->json(['message' => 'Charge fixe supprimée.']);
    }

    /**
     * Échéances dues et non réglées — ce qui alimente l'alerte.
     */
    public function pending(): JsonResponse
    {
        $this->service->genererEcheances();

        $echeances = $this->service->echeancesEnAttente();

        return response()->json([
            'data' => $echeances->map(fn (RecurringExpenseOccurrence $o) => $this->formatEcheance($o))->all(),
            'total' => round((float) $echeances->sum('amount'), 2),
        ]);
    }

    /**
     * Toutes les échéances d'une charge, réglées comprises.
     */
    public function occurrences(RecurringExpense $recurringExpense): JsonResponse
    {
        $echeances = $recurringExpense->occurrences()
            ->with(['paymentMethod:id,name', 'recurringExpense'])
            ->orderByDesc('period')
            ->get();

        return response()->json([
            'data' => $echeances->map(fn (RecurringExpenseOccurrence $o) => $this->formatEcheance($o))->all(),
        ]);
    }

    public function pay(Request $request, RecurringExpenseOccurrence $occurrence): JsonResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $regle = $this->service->regler(
                $occurrence,
                isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                $data['paid_at'] ?? null,
                $request->user()?->id,
                $data['note'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->formatEcheance($regle->load('paymentMethod:id,name'))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function valider(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:191'],
            // Obligatoire : au reglement, l'echeance cree une charge, et la
            // table des charges exige une categorie.
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'start_period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'end_period' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/', 'gte:start_period'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCharge(RecurringExpense $charge): array
    {
        $enAttente = $charge->occurrences()
            ->where('status', RecurringExpenseOccurrence::STATUS_PENDING)->count();

        return [
            'id' => $charge->id,
            'label' => $charge->label,
            'amount' => (float) $charge->amount,
            'day_of_month' => $charge->day_of_month,
            'start_period' => $charge->start_period,
            'end_period' => $charge->end_period,
            'is_active' => $charge->is_active,
            'notes' => $charge->notes,
            'category' => $charge->relationLoaded('category') ? $charge->category?->only(['id', 'name']) : null,
            'warehouse' => $charge->relationLoaded('warehouse') ? $charge->warehouse?->only(['id', 'code', 'name']) : null,
            'pending_count' => $enAttente,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEcheance(RecurringExpenseOccurrence $o): array
    {
        $charge = $o->relationLoaded('recurringExpense')
            ? $o->recurringExpense
            : RecurringExpense::withoutGlobalScopes()->find($o->recurring_expense_id);

        return [
            'id' => $o->id,
            'recurring_expense_id' => $o->recurring_expense_id,
            'label' => $charge?->label,
            'period' => $o->period,
            'due_date' => $o->due_date?->toDateString(),
            'amount' => (float) $o->amount,
            'status' => $o->status,
            'is_overdue' => $o->estEnRetard(),
            'paid_at' => $o->paid_at?->toDateString(),
            'payment_method' => $o->relationLoaded('paymentMethod') ? $o->paymentMethod?->only(['id', 'name']) : null,
            'warehouse' => $charge?->warehouse?->only(['id', 'code', 'name']),
            'category' => $charge?->category?->only(['id', 'name']),
            'note' => $o->note,
        ];
    }
}
