<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Services;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\RecurringExpense;
use App\Domain\Expenses\Models\RecurringExpenseOccurrence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Génération et règlement des échéances de charges fixes.
 */
final class RecurringExpenseService
{
    /**
     * Crée les échéances manquantes, du mois de départ jusqu'au mois courant.
     *
     * La génération est idempotente : l'unicité (charge, période) en base fait
     * qu'un second passage ne crée aucun doublon. On peut donc l'appeler à
     * chaque consultation sans redouter les appels concurrents.
     *
     * @return int Nombre d'échéances créées.
     */
    public function genererEcheances(?Carbon $jusquA = null): int
    {
        $limite = ($jusquA ?? Carbon::today())->format('Y-m');
        $creees = 0;

        // withoutGlobalScopes : la génération est un travail de fond, elle doit
        // couvrir tous les lieux quel que soit l'utilisateur qui la déclenche.
        $charges = RecurringExpense::withoutGlobalScopes()->where('is_active', true)->get();

        foreach ($charges as $charge) {
            $periode = $charge->start_period;

            if ($periode > $limite) {
                continue;
            }

            $fin = $charge->end_period !== null && $charge->end_period < $limite
                ? $charge->end_period
                : $limite;

            while ($periode <= $fin) {
                $existe = RecurringExpenseOccurrence::query()
                    ->where('recurring_expense_id', $charge->id)
                    ->where('period', $periode)
                    ->exists();

                if (! $existe) {
                    RecurringExpenseOccurrence::query()->create([
                        'recurring_expense_id' => $charge->id,
                        'period' => $periode,
                        'due_date' => $charge->dueDateFor($periode),
                        // Le montant est figé à la création : une hausse de
                        // loyer ne doit pas réécrire les mois déjà passés.
                        'amount' => $charge->amount,
                        'status' => RecurringExpenseOccurrence::STATUS_PENDING,
                    ]);
                    $creees++;
                }

                $periode = Carbon::createFromFormat('Y-m-d', $periode.'-01')->addMonth()->format('Y-m');
            }
        }

        return $creees;
    }

    /**
     * Marque une échéance réglée et crée la charge correspondante.
     */
    public function regler(
        RecurringExpenseOccurrence $echeance,
        ?int $paymentMethodId,
        ?string $paidAt,
        ?int $userId,
        ?string $note = null,
    ): RecurringExpenseOccurrence {
        if ($echeance->status === RecurringExpenseOccurrence::STATUS_PAID) {
            throw new RuntimeException('Cette échéance est déjà réglée.');
        }

        return DB::transaction(function () use ($echeance, $paymentMethodId, $paidAt, $userId, $note): RecurringExpenseOccurrence {
            $verrou = RecurringExpenseOccurrence::query()->lockForUpdate()->find($echeance->id);

            if ($verrou === null || $verrou->status === RecurringExpenseOccurrence::STATUS_PAID) {
                throw new RuntimeException('Cette échéance est déjà réglée.');
            }

            $charge = RecurringExpense::withoutGlobalScopes()->find($verrou->recurring_expense_id);
            $date = $paidAt ?? Carbon::today()->toDateString();

            // La charge réelle rejoint le journal des charges : le règlement
            // d'une échéance doit peser dans les comptes comme toute dépense.
            $depense = Expense::withoutGlobalScopes()->create([
                'expense_category_id' => $charge?->expense_category_id,
                'warehouse_id' => $charge?->warehouse_id,
                'user_id' => $userId,
                'label' => ($charge?->label ?? 'Charge fixe').' — '.$verrou->period,
                'amount' => $verrou->amount,
                'payment_method_id' => $paymentMethodId,
                'expense_date' => $date,
                'status' => 'approved',
                'approved_by' => $userId,
            ]);

            $verrou->update([
                'status' => RecurringExpenseOccurrence::STATUS_PAID,
                'paid_at' => $date,
                'payment_method_id' => $paymentMethodId,
                'expense_id' => $depense->id,
                'paid_by' => $userId,
                'note' => $note,
            ]);

            return $verrou->refresh();
        });
    }

    /**
     * Échéances dues et non réglées, la plus ancienne d'abord.
     *
     * @return \Illuminate\Support\Collection<int, RecurringExpenseOccurrence>
     */
    public function echeancesEnAttente(?Carbon $au = null)
    {
        $date = ($au ?? Carbon::today())->toDateString();

        return RecurringExpenseOccurrence::query()
            ->with(['recurringExpense.category', 'recurringExpense.warehouse'])
            // L'échéance ne porte pas de lieu : le cloisonnement vient de la
            // charge parente, dont le scope s'applique dans ce whereHas.
            ->whereHas('recurringExpense')
            ->where('status', RecurringExpenseOccurrence::STATUS_PENDING)
            ->whereDate('due_date', '<=', $date)
            ->orderBy('due_date')
            ->get();
    }
}
