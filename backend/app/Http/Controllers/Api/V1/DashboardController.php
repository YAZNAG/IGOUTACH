<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Stock\Services\StockOverviewService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    /**
     * Vue globale consolidée (réservée à stock.view_global).
     */
    public function index(StockOverviewService $overview): JsonResponse
    {
        return response()->json([
            'data' => [
                'summary' => $overview->summary(),
                'stock' => $overview->consolidatedStock(),
            ],
        ]);
    }
}
