<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Séries chronologiques et répartitions alimentant les graphiques du tableau
 * de bord.
 *
 * Les agrégations sont faites au jour (fonction DATE(), portable) puis
 * recomposées en PHP pour les vues mensuelles : une seule requête couvre à la
 * fois la courbe 30 jours et l'histogramme 6 mois, et les mois sans écriture
 * apparaissent quand même dans la série — un graphique qui saute des périodes
 * vides laisse croire à une continuité qui n'existe pas.
 */
final class DashboardMetricsService
{
    /**
     * Chiffre d'affaires et nombre de ventes, jour par jour, sur N jours.
     *
     * @return list<array{date: string, label: string, revenue: float, count: int}>
     */
    public function salesTrend(int $days = 30): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = Sale::withoutGlobalScopes()
            ->selectRaw('DATE(confirmed_at) as jour, SUM(total) as ca, COUNT(*) as nb')
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', $from)
            ->groupBy('jour')
            ->get()
            ->keyBy(fn ($row) => (string) $row->getAttribute('jour'));

        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $row = $rows->get($date->toDateString());

            $series[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'revenue' => $row !== null ? round((float) $row->getAttribute('ca'), 2) : 0.0,
                'count' => $row !== null ? (int) $row->getAttribute('nb') : 0,
            ];
        }

        return $series;
    }

    /**
     * Ventes vs achats, mois par mois, sur N mois glissants.
     *
     * @return list<array{month: string, label: string, sales: float, purchases: float}>
     */
    public function monthlyFlow(int $months = 6): array
    {
        $from = Carbon::today()->startOfMonth()->subMonths($months - 1);

        $sales = Sale::withoutGlobalScopes()
            ->selectRaw('DATE(confirmed_at) as jour, SUM(total) as montant')
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', $from)
            ->groupBy('jour')
            ->pluck('montant', 'jour');

        $purchases = DB::table('goods_receipt_lines')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->selectRaw('DATE(goods_receipts.received_at) as jour, SUM(goods_receipt_lines.quantity * goods_receipt_lines.unit_price) as montant')
            ->where('goods_receipts.received_at', '>=', $from)
            ->groupBy('jour')
            ->pluck('montant', 'jour');

        $buckets = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $from->copy()->addMonths($i);
            $buckets[$month->format('Y-m')] = [
                'month' => $month->format('Y-m'),
                'label' => $this->moisCourt($month),
                'sales' => 0.0,
                'purchases' => 0.0,
            ];
        }

        foreach ($sales as $jour => $montant) {
            $key = substr((string) $jour, 0, 7);
            if (isset($buckets[$key])) {
                $buckets[$key]['sales'] += (float) $montant;
            }
        }

        foreach ($purchases as $jour => $montant) {
            $key = substr((string) $jour, 0, 7);
            if (isset($buckets[$key])) {
                $buckets[$key]['purchases'] += (float) $montant;
            }
        }

        return array_values($buckets);
    }

    /**
     * Répartition du stock par lieu : unités détenues et valeur au CMUP.
     *
     * @return list<array{warehouse: string, units: int, value: float}>
     */
    public function stockByWarehouse(): array
    {
        return DB::table('stocks')
            ->join('warehouses', 'warehouses.id', '=', 'stocks.warehouse_id')
            ->selectRaw('warehouses.code as code, warehouses.name as nom, SUM(stocks.quantity) as unites, SUM(stocks.quantity * stocks.average_cost) as valeur')
            ->where('warehouses.is_active', true)
            ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
            ->orderByDesc('valeur')
            ->get()
            ->map(fn ($row) => [
                'warehouse' => $row->code,
                'name' => $row->nom,
                'units' => (int) $row->unites,
                'value' => round((float) $row->valeur, 2),
            ])
            ->all();
    }

    /**
     * Meilleures ventes sur la période, en chiffre d'affaires.
     *
     * @return list<array{name: string, quantity: int, revenue: float}>
     */
    public function topProducts(int $days = 30, int $limit = 6): array
    {
        $from = Carbon::today()->subDays($days - 1);

        return DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->selectRaw('products.name as nom, SUM(sale_lines.quantity) as qte, SUM(sale_lines.line_total) as ca')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('ca')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->nom,
                'quantity' => (int) $row->qte,
                'revenue' => round((float) $row->ca, 2),
            ])
            ->all();
    }

    /**
     * Chiffre d'affaires mois par mois pour une entité donnée.
     *
     * Le même calcul sert au lieu, au client et au fournisseur : seule la
     * colonne filtrée change. Les mois sans activité valent zéro — une courbe
     * qui saute les mois creux laisserait croire à une activité continue.
     *
     * @param  'warehouse_id'|'customer_id'  $colonne
     * @return list<array{month: string, label: string, revenue: float, count: int}>
     */
    public function monthlyRevenueFor(string $colonne, int $id, int $months = 12): array
    {
        $debut = Carbon::today()->startOfMonth()->subMonths($months - 1);

        // Regroupement par jour puis agrégation en PHP : la mise en forme du
        // mois côté base varie d'un moteur à l'autre, pas celle-ci.
        //
        // Le coût se lit sur les lignes, pas sur l'en-tête de la vente : une
        // sous-requête plutôt qu'une jointure, sinon chaque vente serait
        // comptée autant de fois qu'elle a d'articles et le chiffre
        // d'affaires se trouverait multiplié.
        $couts = DB::table('sale_lines')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->selectRaw('sale_lines.sale_id, SUM(sale_lines.quantity * products.cost_price) as cout')
            ->groupBy('sale_lines.sale_id');

        $lignes = Sale::withoutGlobalScopes()
            ->leftJoinSub($couts, 'c', 'c.sale_id', '=', 'sales.id')
            ->selectRaw('DATE(sales.confirmed_at) as jour, SUM(sales.total) as ca, COUNT(*) as nb, COALESCE(SUM(c.cout), 0) as cout')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.'.$colonne, $id)
            ->where('sales.confirmed_at', '>=', $debut)
            ->groupBy('jour')
            ->get();

        $paniers = [];

        for ($i = 0; $i < $months; $i++) {
            $mois = $debut->copy()->addMonths($i);
            $paniers[$mois->format('Y-m')] = [
                'month' => $mois->format('Y-m'),
                'label' => $this->moisCourt($mois),
                'revenue' => 0.0,
                'cost' => 0.0,
                'profit' => 0.0,
                'count' => 0,
            ];
        }

        foreach ($lignes as $ligne) {
            $cle = substr((string) $ligne->getAttribute('jour'), 0, 7);
            if (isset($paniers[$cle])) {
                $paniers[$cle]['revenue'] = round($paniers[$cle]['revenue'] + (float) $ligne->getAttribute('ca'), 2);
                $paniers[$cle]['cost'] = round($paniers[$cle]['cost'] + (float) $ligne->getAttribute('cout'), 2);
                $paniers[$cle]['profit'] = round($paniers[$cle]['revenue'] - $paniers[$cle]['cost'], 2);
                $paniers[$cle]['count'] += (int) $ligne->getAttribute('nb');
            }
        }

        return array_values($paniers);
    }

    /**
     * Achats mois par mois auprès d'un fournisseur.
     *
     * Le pendant de [monthlyRevenueFor] côté entrées : les réceptions n'ont
     * pas de total stocké, il se recompose depuis leurs lignes.
     *
     * @return list<array{month: string, label: string, purchases: float, count: int}>
     */
    public function monthlyPurchasesFor(int $supplierId, int $months = 12): array
    {
        $debut = Carbon::today()->startOfMonth()->subMonths($months - 1);

        $lignes = DB::table('goods_receipts')
            ->join('goods_receipt_lines as l', 'l.goods_receipt_id', '=', 'goods_receipts.id')
            ->selectRaw('DATE(goods_receipts.received_at) as jour')
            ->selectRaw('SUM(l.quantity * l.unit_price) as achats, COUNT(DISTINCT goods_receipts.id) as nb')
            ->where('goods_receipts.supplier_id', $supplierId)
            ->where('goods_receipts.received_at', '>=', $debut)
            ->groupBy('jour')
            ->get();

        $paniers = [];

        for ($i = 0; $i < $months; $i++) {
            $mois = $debut->copy()->addMonths($i);
            $paniers[$mois->format('Y-m')] = [
                'month' => $mois->format('Y-m'),
                'label' => $this->moisCourt($mois),
                'purchases' => 0.0,
                'count' => 0,
            ];
        }

        foreach ($lignes as $ligne) {
            $cle = substr((string) $ligne->jour, 0, 7);
            if (isset($paniers[$cle])) {
                $paniers[$cle]['purchases'] = round($paniers[$cle]['purchases'] + (float) $ligne->achats, 2);
                $paniers[$cle]['count'] += (int) $ligne->nb;
            }
        }

        return array_values($paniers);
    }

    /**
     * Encours clients par tranche d'ancienneté.
     *
     * Une créance de 30 jours et une de 120 ne valent pas la même chose : le
     * total seul masque exactement ce qui inquiète.
     *
     * @return list<array{bucket: string, amount: float}>
     */
    public function agingBuckets(): array
    {
        $aujourdhui = Carbon::today();

        $tranches = [
            ['0 – 30 j', 0, 30],
            ['31 – 60 j', 31, 60],
            ['61 – 90 j', 61, 90],
            ['+ de 90 j', 91, 100000],
        ];

        $factures = Sale::withoutGlobalScopes()
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereRaw('paid_amount < total')
            ->get(['confirmed_at', 'total', 'paid_amount']);

        return array_map(function (array $t) use ($factures, $aujourdhui): array {
            [$libelle, $min, $max] = $t;

            $montant = $factures
                ->filter(function ($f) use ($min, $max, $aujourdhui): bool {
                    $age = $f->confirmed_at === null ? 0 : $aujourdhui->diffInDays($f->confirmed_at, absolute: true);

                    return $age >= $min && $age <= $max;
                })
                ->sum(fn ($f): float => (float) $f->total - (float) $f->paid_amount);

            return ['bucket' => $libelle, 'amount' => round((float) $montant, 2)];
        }, $tranches);
    }

    /**
     * Charges du mois par catégorie.
     *
     * @return list<array{name: string, amount: float, count: int}>
     */
    public function expensesByCategory(int $days = 30): array
    {
        $depuis = Carbon::today()->subDays($days - 1);

        return DB::table('expenses')
            ->join('expense_categories as c', 'c.id', '=', 'expenses.expense_category_id')
            ->selectRaw('c.name as nom, SUM(expenses.amount) as montant, COUNT(*) as nb')
            ->where('expenses.expense_date', '>=', $depuis->toDateString())
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('montant')
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->nom,
                'amount' => round((float) $r->montant, 2),
                'count' => (int) $r->nb,
            ])
            ->all();
    }

    /**
     * Chiffre d'affaires par lieu de vente.
     *
     * La valeur du stock dit ce qu'un lieu détient ; celle-ci dit ce qu'il
     * rapporte. Un dépôt bien garni qui ne vend rien ne se voit que sur ce
     * chiffre-là.
     *
     * @return list<array{warehouse: string, name: string, count: int, revenue: float}>
     */
    public function revenueByWarehouse(int $days = 30): array
    {
        $from = Carbon::today()->subDays($days - 1);

        return DB::table('sales')
            ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->selectRaw('warehouses.code as code, warehouses.name as nom, COUNT(*) as nb, SUM(sales.total) as ca')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
            ->orderByDesc('ca')
            ->get()
            ->map(fn ($row) => [
                'warehouse' => (string) $row->code,
                'name' => (string) $row->nom,
                'count' => (int) $row->nb,
                'revenue' => round((float) $row->ca, 2),
            ])
            ->all();
    }

    /**
     * Meilleurs clients sur la période, avec ce qu'ils doivent encore.
     *
     * @return list<array{name: string, count: int, revenue: float, balance: float}>
     */
    public function topCustomers(int $days = 30, int $limit = 8): array
    {
        $from = Carbon::today()->subDays($days - 1);

        return DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->selectRaw('customers.name as nom, customers.balance as encours, COUNT(*) as nb, SUM(sales.total) as ca')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->groupBy('customers.id', 'customers.name', 'customers.balance')
            ->orderByDesc('ca')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->nom,
                'count' => (int) $row->nb,
                'revenue' => round((float) $row->ca, 2),
                'balance' => round((float) $row->encours, 2),
            ])
            ->all();
    }

    /**
     * Achats par fournisseur sur la période, et ce qu'on leur doit.
     *
     * Le pendant des meilleurs clients : d'un côté ce qui rentre, de l'autre
     * ce qui sort. Les deux se lisent ensemble.
     *
     * @return list<array{name: string, count: int, purchases: float, due: float}>
     */
    public function topSuppliers(int $days = 30, int $limit = 8): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $total = DB::table('goods_receipt_lines')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
            ->whereColumn('goods_receipt_id', 'goods_receipts.id');

        return DB::table('goods_receipts')
            ->join('suppliers', 'suppliers.id', '=', 'goods_receipts.supplier_id')
            ->selectRaw('suppliers.name as nom, COUNT(*) as nb')
            ->selectSub("SUM(({$total->toSql()}))", 'achats')
            ->selectSub("SUM(({$total->toSql()}) - goods_receipts.amount_paid)", 'reste')
            ->mergeBindings($total)
            ->mergeBindings($total)
            ->where('goods_receipts.received_at', '>=', $from)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('achats')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->nom,
                'count' => (int) $row->nb,
                'purchases' => round((float) $row->achats, 2),
                'due' => round(max(0, (float) $row->reste), 2),
            ])
            ->all();
    }

    /**
     * Répartition des ventes confirmées par état de règlement.
     *
     * @return list<array{status: string, label: string, count: int, amount: float}>
     */
    public function paymentMix(int $days = 30): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = Sale::withoutGlobalScopes()
            ->selectRaw('payment_status, COUNT(*) as nb, SUM(total) as montant')
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', $from)
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        $libelles = [
            'paid' => 'Payé',
            'partial' => 'Partiellement payé',
            'unpaid' => 'À crédit',
        ];

        $mix = [];

        foreach ($libelles as $code => $label) {
            $row = $rows->get($code);

            $mix[] = [
                'status' => $code,
                'label' => $label,
                'count' => $row !== null ? (int) $row->getAttribute('nb') : 0,
                'amount' => $row !== null ? round((float) $row->getAttribute('montant'), 2) : 0.0,
            ];
        }

        return $mix;
    }

    /**
     * Indicateurs financiers du mois en cours.
     *
     * @return array{revenue_month: float, sales_month: int, outstanding: float, stock_value: float}
     */
    public function financialSummary(): array
    {
        $startOfMonth = Carbon::today()->startOfMonth();

        $month = Sale::withoutGlobalScopes()
            ->selectRaw('COALESCE(SUM(total), 0) as ca, COUNT(*) as nb')
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', $startOfMonth)
            ->first();

        $outstanding = (float) DB::table('customers')->sum('balance');
        $stockValue = (float) DB::table('stocks')->selectRaw('COALESCE(SUM(quantity * average_cost), 0) as v')->value('v');

        return [
            'revenue_month' => round((float) ($month?->getAttribute('ca') ?? 0), 2),
            'sales_month' => (int) ($month?->getAttribute('nb') ?? 0),
            'outstanding' => round($outstanding, 2),
            'stock_value' => round($stockValue, 2),
        ];
    }

    private function moisCourt(Carbon $date): string
    {
        $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        return $mois[$date->month - 1].' '.$date->format('y');
    }
}
