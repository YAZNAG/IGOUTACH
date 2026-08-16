<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Services\DashboardMetricsService;
use App\Domain\Stock\Services\StockOverviewService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    /**
     * Vue globale consolidée (réservée à stock.view_global).
     */
    public function index(StockOverviewService $overview, DashboardMetricsService $metrics): JsonResponse
    {
        return response()->json([
            'data' => [
                'summary' => $overview->summary(),
                'financial' => $metrics->financialSummary(),
                'sales_trend' => $metrics->salesTrend(30),
                'monthly_flow' => $metrics->monthlyFlow(6),
                'stock_by_warehouse' => $metrics->stockByWarehouse(),
                'top_products' => $metrics->topProducts(30),
                // Le chiffre d'affaires vu sous ses trois angles : où l'on
                // vend, à qui l'on vend, et à qui l'on achète.
                'revenue_by_warehouse' => $metrics->revenueByWarehouse(30),
                'top_customers' => $metrics->topCustomers(30),
                'top_suppliers' => $metrics->topSuppliers(30),
                'payment_mix' => $metrics->paymentMix(30),
                // Une créance de 30 jours et une de 120 ne valent pas la même
                // chose : le total seul masque ce qui inquiète.
                'aging' => $metrics->agingBuckets(),
                'expenses_by_category' => $metrics->expensesByCategory(30),
                'stock' => $overview->consolidatedStock(20),
            ],
        ]);
    }
}
