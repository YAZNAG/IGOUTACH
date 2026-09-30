<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Models\CashRemittance;
use App\Domain\Sales\Models\CashSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il doit y avoir dans le tiroir.
 *
 * Le solde d'une caisse n'est pas le total encaissé : c'est le fonds de
 * départ, plus ce qui est entré en espèces, moins ce qui en est sorti — les
 * charges payées de la main à la main et les remises faites à
 * l'administration. Compter les entrées sans les sorties donnait un attendu
 * toujours supérieur au réel, et donc un écart permanent que personne ne
 * pouvait expliquer.
 *
 * Seules les espèces comptent. Un chèque ou un virement ne passe pas par le
 * tiroir : l'y inclure ferait réclamer au responsable un argent qu'il n'a
 * jamais eu entre les mains.
 */
final class CashBoxService
{
    /**
     * Détail du solde d'un lieu sur la période d'une session.
     *
     * Sans session ouverte, [$depuis] cadre la journée en cours : le
     * responsable voit quand même ce qui est passé par sa caisse.
     *
     * @return array{opening: float, cash_in: float, cash_expenses: float, remitted: float, expected: float}
     */
    public function solde(int $warehouseId, ?CashSession $session = null, ?Carbon $depuis = null): array
    {
        $debut = $session?->opened_at ?? $depuis ?? Carbon::today();
        $fin = $session?->closed_at;

        $fonds = (float) ($session?->opening_amount ?? 0);
        $entrees = $this->encaissementsEspeces($warehouseId, $debut, $fin);
        $charges = $this->chargesEspeces($warehouseId, $debut, $fin);
        $remises = $this->remises($warehouseId, $debut, $fin);

        return [
            'opening' => round($fonds, 2),
            'cash_in' => round($entrees, 2),
            'cash_expenses' => round($charges, 2),
            'remitted' => round($remises, 2),
            'expected' => round($fonds + $entrees - $charges - $remises, 2),
        ];
    }

    /**
     * Mouvements du tiroir d'un lieu sur une période libre (hors fonds de
     * caisse) : ce qui est entré en espèces, sorti en charges et remis.
     *
     * @return array{cash_in: float, cash_expenses: float, remitted: float, remaining: float}
     */
    public function soldePeriode(int $warehouseId, Carbon $debut, Carbon $fin): array
    {
        $entrees = $this->encaissementsEspeces($warehouseId, $debut, $fin);
        $charges = $this->chargesEspeces($warehouseId, $debut, $fin);
        $remises = $this->remises($warehouseId, $debut, $fin);

        return [
            'cash_in' => round($entrees, 2),
            'cash_expenses' => round($charges, 2),
            'remitted' => round($remises, 2),
            'remaining' => round($entrees - $charges - $remises, 2),
        ];
    }

    /**
     * Encaissements en espèces rattachés au lieu.
     *
     * Un règlement suit sa facture ; sans facture, il suit celui qui l'a pris,
     * donc son lieu de rattachement.
     */
    private function encaissementsEspeces(int $warehouseId, Carbon $debut, ?Carbon $fin): float
    {
        return $this->reglementsEspeces($warehouseId, $debut, $fin)
            + $this->ventesComptoir($warehouseId, $debut, $fin);
    }

    /**
     * Ventes au client de passage.
     *
     * Sans fiche client, la vente est reputee payee comptant a sa validation,
     * mais aucun reglement n'est enregistre (un reglement exige un client) :
     * la caisse les ignorait, et le tiroir contenait plus que le solde
     * affiche. Elles comptent ici, a leur date de validation.
     */
    private function ventesComptoir(int $warehouseId, Carbon $debut, ?Carbon $fin): float
    {
        return (float) DB::table('sales')
            ->where('warehouse_id', $warehouseId)
            ->whereNull('customer_id')
            ->where('type', 'invoice')
            ->where('status', 'confirmed')
            ->where('confirmed_at', '>=', $debut)
            ->when($fin !== null, fn ($q) => $q->where('confirmed_at', '<=', $fin))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('payments')->whereColumn('payments.sale_id', 'sales.id'))
            ->sum('paid_amount');
    }

    private function reglementsEspeces(int $warehouseId, Carbon $debut, ?Carbon $fin): float
    {
        return (float) DB::table('payments')
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->leftJoin('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('users', 'users.id', '=', 'payments.user_id')
            ->where('payment_methods.type', 'cash')
            ->whereRaw('COALESCE(sales.warehouse_id, users.warehouse_id) = ?', [$warehouseId])
            ->where('payments.created_at', '>=', $debut)
            ->when($fin !== null, fn ($q) => $q->where('payments.created_at', '<=', $fin))
            ->sum('payments.amount');
    }

    /**
     * Charges réglées en espèces depuis le tiroir.
     *
     * La date de règlement d'une charge n'a pas d'heure : on la compare donc
     * au jour, pas à l'horodatage d'ouverture. Une charge payée le matin
     * compte dans la session ouverte l'après-midi du même jour — c'est le
     * comportement voulu tant qu'un lieu tient une session par jour, ce qui
     * est le cas ici. Comparer à l'heure les écartait toutes silencieusement.
     */
    private function chargesEspeces(int $warehouseId, Carbon $debut, ?Carbon $fin): float
    {
        return (float) DB::table('expenses')
            ->join('payment_methods', 'payment_methods.id', '=', 'expenses.payment_method_id')
            ->where('payment_methods.type', 'cash')
            ->where('expenses.warehouse_id', $warehouseId)
            ->where('expenses.payment_status', 'paid')
            ->whereRaw('COALESCE(expenses.paid_at, DATE(expenses.created_at)) >= ?', [$debut->toDateString()])
            ->when($fin !== null, fn ($q) => $q->whereRaw(
                'COALESCE(expenses.paid_at, DATE(expenses.created_at)) <= ?',
                [$fin->toDateString()],
            ))
            ->sum('expenses.amount');
    }

    /**
     * Remises faites à l'administration.
     *
     * Comptées dès la déclaration, pas à la confirmation : l'argent a quitté
     * le tiroir au moment où il a été remis, pas au moment où la direction
     * l'enregistre.
     */
    private function remises(int $warehouseId, Carbon $debut, ?Carbon $fin): float
    {
        return (float) CashRemittance::withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
            // Un transfert refuse n'a jamais quitte le tiroir : la somme
            // revient au lieu, qui doit la retrouver dans son solde.
            ->where('status', '!=', CashRemittance::STATUS_REFUSED)
            ->where('created_at', '>=', $debut)
            ->when($fin !== null, fn ($q) => $q->where('created_at', '<=', $fin))
            ->sum('amount');
    }
}
