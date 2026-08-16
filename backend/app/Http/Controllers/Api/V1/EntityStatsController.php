<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Services\DashboardMetricsService;
use App\Domain\Warehouses\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Activité chiffrée d'un lieu, d'un client ou d'un fournisseur.
 *
 * Le tableau de bord dit qui pèse le plus ; ces pages disent comment chacun a
 * évolué. Le classement sans l'historique laisse croire qu'un premier de la
 * liste est un client qui monte, alors qu'il peut être un client qui s'arrête.
 */
final class EntityStatsController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics) {}

    /**
     * Ce qu'un lieu vend : série mensuelle, totaux, meilleurs articles et
     * meilleurs clients.
     */
    public function warehouse(Warehouse $warehouse): JsonResponse
    {
        $serie = $this->metrics->monthlyRevenueFor('warehouse_id', $warehouse->id);

        return response()->json(['data' => [
            'monthly' => $serie,
            'totals' => $this->totaux($serie, 'revenue'),
            'top_products' => $this->meilleursArticles(['sales.warehouse_id' => $warehouse->id]),
            'top_customers' => $this->meilleursClients($warehouse->id),
        ]]);
    }

    /**
     * Ce qu'un client achète : série mensuelle, totaux, articles préférés.
     */
    public function customer(Customer $customer): JsonResponse
    {
        $serie = $this->metrics->monthlyRevenueFor('customer_id', $customer->id);

        return response()->json(['data' => [
            'monthly' => $serie,
            'totals' => $this->totaux($serie, 'revenue'),
            'top_products' => $this->meilleursArticles(['sales.customer_id' => $customer->id]),
        ]]);
    }

    /**
     * Ce qu'un fournisseur livre : série mensuelle des achats et totaux.
     */
    public function supplier(Supplier $supplier): JsonResponse
    {
        $serie = $this->metrics->monthlyPurchasesFor($supplier->id);

        return response()->json(['data' => [
            'monthly' => $serie,
            'totals' => $this->totaux($serie, 'purchases'),
            'top_products' => $this->articlesLivres($supplier->id),
        ]]);
    }

    /**
     * Cumul, moyenne mensuelle et variation du dernier mois clos.
     *
     * La variation se lit sur les deux derniers mois complets : comparer un
     * mois en cours à un mois plein annoncerait une chute tous les 1ers du
     * mois.
     *
     * @param  list<array<string, mixed>>  $serie
     * @return array<string, mixed>
     */
    private function totaux(array $serie, string $cle): array
    {
        $valeurs = array_column($serie, $cle);
        $total = array_sum($valeurs);
        $nombre = count($serie);

        $moisClos = $nombre >= 2 ? (float) $valeurs[$nombre - 2] : 0.0;
        $moisPrecedent = $nombre >= 3 ? (float) $valeurs[$nombre - 3] : 0.0;

        return [
            'total' => round((float) $total, 2),
            'average' => $nombre > 0 ? round((float) $total / $nombre, 2) : 0.0,
            'best' => $valeurs === [] ? 0.0 : round((float) max($valeurs), 2),
            'current_month' => $nombre > 0 ? round((float) $valeurs[$nombre - 1], 2) : 0.0,
            'last_closed_month' => round($moisClos, 2),
            'change_percent' => $moisPrecedent > 0
                ? round((($moisClos - $moisPrecedent) / $moisPrecedent) * 100, 1)
                : null,
            'documents' => (int) array_sum(array_column($serie, 'count')),
        ];
    }

    /**
     * @param  array<string, int>  $filtre
     * @return list<array<string, mixed>>
     */
    private function meilleursArticles(array $filtre): array
    {
        $query = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->selectRaw('products.sku as sku, products.name as nom, SUM(sale_lines.quantity) as qte, SUM(sale_lines.line_total) as ca')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED);

        foreach ($filtre as $colonne => $valeur) {
            $query->where($colonne, $valeur);
        }

        return $query
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->orderByDesc('ca')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'sku' => (string) $r->sku,
                'name' => (string) $r->nom,
                'quantity' => (int) $r->qte,
                'revenue' => round((float) $r->ca, 2),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function meilleursClients(int $warehouseId): array
    {
        return DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->selectRaw('customers.name as nom, COUNT(*) as nb, SUM(sales.total) as ca')
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.warehouse_id', $warehouseId)
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('ca')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->nom,
                'count' => (int) $r->nb,
                'revenue' => round((float) $r->ca, 2),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articlesLivres(int $supplierId): array
    {
        return DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as r', 'r.id', '=', 'l.goods_receipt_id')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->selectRaw('p.sku as sku, p.name as nom, SUM(l.quantity) as qte, SUM(l.quantity * l.unit_price) as montant')
            ->where('r.supplier_id', $supplierId)
            ->groupBy('p.id', 'p.sku', 'p.name')
            ->orderByDesc('montant')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'sku' => (string) $r->sku,
                'name' => (string) $r->nom,
                'quantity' => (int) $r->qte,
                'revenue' => round((float) $r->montant, 2),
            ])
            ->all();
    }
}
