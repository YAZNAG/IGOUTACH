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
