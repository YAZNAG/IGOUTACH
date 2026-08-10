<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use App\Support\Scopes\WarehouseScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord du lieu de l'utilisateur.
 *
 * Un seul appel plutôt que cinq : l'écran d'accueil se charge d'un coup, ce
 * qui compte sur un téléphone en 3G dans un magasin.
 *
 * Tout est cadré sur le lieu rattaché. Un responsable ne voit jamais les
 * chiffres d'un autre, et la direction obtient le consolidé.
 */
final class MyOverviewController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $global = $user !== null && $user->can(WarehouseScope::DEFAULT_GLOBAL_PERMISSION);
        $lieu = $global ? null : $user?->getAttribute('warehouse_id');

        $aujourdhui = Carbon::today();
        $debutMois = $aujourdhui->copy()->startOfMonth();

        return response()->json(['data' => [
            'warehouse' => $this->lieu($user, $global),
            'today' => $this->ventes($lieu, $aujourdhui, $aujourdhui),
            'month' => $this->ventes($lieu, $debutMois, $aujourdhui),
            'stock' => $this->stock($lieu),
            'receivables' => $this->creances($lieu),
            'expenses_month' => $this->charges($lieu, $debutMois, $aujourdhui),
            'top_products' => $this->meilleuresVentes($lieu, $debutMois),
            'daily' => $this->serieJournaliere($lieu, 14),
            'pending' => $this->aTraiter($lieu),
        ]]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lieu($user, bool $global): ?array
    {
        if ($global) {
            return ['code' => 'TOUS', 'name' => 'Tous les lieux'];
        }

        $w = $user?->warehouse;

        return $w === null ? null : ['id' => $w->id, 'code' => $w->code, 'name' => $w->name];
    }

    /**
     * @return array<string, mixed>
     */
    private function ventes(?int $lieu, Carbon $du, Carbon $au): array
    {
        $r = Sale::withoutGlobalScopes()
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->when($lieu !== null, fn ($q) => $q->where('warehouse_id', $lieu))
            ->whereDate('confirmed_at', '>=', $du->toDateString())
            ->whereDate('confirmed_at', '<=', $au->toDateString())
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(total),0) as ca, COALESCE(SUM(paid_amount),0) as encaisse')
            ->first();

        $ca = round((float) ($r?->ca ?? 0), 2);
        $encaisse = round((float) ($r?->encaisse ?? 0), 2);

        return [
            'count' => (int) ($r?->nb ?? 0),
            'revenue' => $ca,
            'collected' => $encaisse,
            // Ce qui reste à encaisser sur la période : le chiffre qui dit
            // si l'on vend à crédit sans s'en rendre compte.
            'on_credit' => round($ca - $encaisse, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stock(?int $lieu): array
    {
        $base = DB::table('stocks')
            ->when($lieu !== null, fn ($q) => $q->where('warehouse_id', $lieu));

        $valeur = (float) (clone $base)->sum(DB::raw('quantity * average_cost'));
        $unites = (int) (clone $base)->sum('quantity');
        $refs = (int) (clone $base)->where('quantity', '>', 0)->distinct()->count('product_id');
        $ruptures = (int) (clone $base)->where('quantity', '<=', 0)->count();

        $sousSeuil = (int) DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->when($lieu !== null, fn ($q) => $q->where('stocks.warehouse_id', $lieu))
            ->whereNotNull('products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->whereColumn('stocks.quantity', '<', 'products.min_stock')
            ->where('stocks.quantity', '>', 0)
            ->count();

        return [
            'value' => round($valeur, 2),
            'units' => $unites,
            'references' => $refs,
            'below_min' => $sousSeuil,
            'out_of_stock' => $ruptures,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creances(?int $lieu): array
    {
        // L'encours vit sur la fiche client, sans rattachement à un lieu :
        // on le rapporte aux clients ayant acheté ici.
        $clients = DB::table('customers')->select('customers.id', 'customers.balance', 'customers.credit_limit')
            ->when($lieu !== null, fn ($q) => $q->whereIn(
                'customers.id',
                DB::table('sales')->select('customer_id')->where('warehouse_id', $lieu)->whereNotNull('customer_id'),
            ))
            ->where('customers.balance', '>', 0)
            ->get();

        return [
            'total' => round((float) $clients->sum('balance'), 2),
            'customers' => $clients->count(),
            'over_limit' => $clients->filter(
                fn ($c) => (float) $c->credit_limit > 0 && (float) $c->balance > (float) $c->credit_limit,
            )->count(),
        ];
    }

    private function charges(?int $lieu, Carbon $du, Carbon $au): float
    {
        return round((float) Expense::withoutGlobalScopes()
            ->when($lieu !== null, fn ($q) => $q->where('warehouse_id', $lieu))
            ->whereDate('expense_date', '>=', $du->toDateString())
            ->whereDate('expense_date', '<=', $au->toDateString())
            ->sum('amount'), 2);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function meilleuresVentes(?int $lieu, Carbon $depuis): array
    {
        return DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->when($lieu !== null, fn ($q) => $q->where('sales.warehouse_id', $lieu))
            ->whereDate('sales.confirmed_at', '>=', $depuis->toDateString())
            ->selectRaw('products.name as nom, SUM(sale_lines.quantity) as qte, SUM(sale_lines.line_total) as ca')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('ca')
            ->limit(5)
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->nom,
                'quantity' => (int) $r->qte,
                'revenue' => round((float) $r->ca, 2),
            ])->all();
    }

    /**
     * Chiffre d'affaires des N derniers jours, trous compris.
     *
     * @return list<array{date: string, label: string, revenue: float}>
     */
    private function serieJournaliere(?int $lieu, int $jours): array
    {
        $depuis = Carbon::today()->subDays($jours - 1);

        $lignes = Sale::withoutGlobalScopes()
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->when($lieu !== null, fn ($q) => $q->where('warehouse_id', $lieu))
            ->whereDate('confirmed_at', '>=', $depuis->toDateString())
            ->selectRaw('DATE(confirmed_at) as jour, SUM(total) as ca')
            ->groupBy('jour')
            ->pluck('ca', 'jour');

        $serie = [];

        for ($i = 0; $i < $jours; $i++) {
            $d = $depuis->copy()->addDays($i);
            // Un jour sans vente vaut zéro : une courbe qui saute les jours
            // creux laisserait croire à une activité continue.
            $serie[] = [
                'date' => $d->toDateString(),
                'label' => $d->format('d/m'),
                'revenue' => round((float) ($lignes[$d->toDateString()] ?? 0), 2),
            ];
        }

        return $serie;
    }

    /**
     * Ce qui attend une action de l'utilisateur.
     *
     * @return array<string, int>
     */
    private function aTraiter(?int $lieu): array
    {
        return [
            // Demandes de transfert que ce lieu doit accorder.
            'transfer_requests' => (int) DB::table('transfers')
                ->join('transfer_statuses as ts', 'ts.id', '=', 'transfers.transfer_status_id')
                ->where('ts.code', 'requested')
                ->when($lieu !== null, fn ($q) => $q->where('transfers.from_warehouse_id', $lieu))
                ->count(),
            // Marchandise expédiée vers ce lieu, en attente de réception.
            'incoming_transfers' => (int) DB::table('transfers')
                ->join('transfer_statuses as ts', 'ts.id', '=', 'transfers.transfer_status_id')
                ->where('ts.code', 'in_transit')
                ->when($lieu !== null, fn ($q) => $q->where('transfers.to_warehouse_id', $lieu))
                ->count(),
            'draft_inventories' => (int) DB::table('inventories')
                ->where('status', 'draft')
                ->when($lieu !== null, fn ($q) => $q->where('warehouse_id', $lieu))
                ->count(),
            'unpaid_sales' => (int) Sale::withoutGlobalScopes()
                ->where('type', Sale::TYPE_INVOICE)
                ->where('status', Sale::STATUS_CONFIRMED)
                ->where('payment_status', '!=', 'paid')
                ->when($lieu !== null, fn ($q) => $q->where('warehouse_id', $lieu))
                ->count(),
        ];
    }
}
