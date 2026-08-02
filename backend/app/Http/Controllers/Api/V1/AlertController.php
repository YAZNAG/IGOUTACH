<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Alertes consolidées du pilotage : stock sous seuil, prix sous plancher,
 * clients hors plafond, factures échues, transferts en retard, inventaires
 * et charges en attente.
 */
final class AlertController extends Controller
{
    public function index(): JsonResponse
    {
        $lowStock = DB::table('products')
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->whereNotNull('products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) < products.min_stock')
            ->count(DB::raw('DISTINCT products.id'));

        $belowFloor = DB::table('product_prices')
            ->join('products', 'products.id', '=', 'product_prices.product_id')
            ->whereNull('product_prices.valid_to')
            ->where('product_prices.min_margin_percent', '>', 0)
            ->whereRaw('product_prices.amount < products.cost_price * (1 + product_prices.min_margin_percent / 100)')
            ->count();

        $overLimit = DB::table('customers')
            ->where('credit_limit', '>', 0)
            ->whereColumn('balance', '>', 'credit_limit')
            ->count();

        $inTransitId = TransferStatus::query()->where('code', TransferStatus::IN_TRANSIT)->value('id');
        $lateTransfers = Transfer::query()
            ->where('transfer_status_id', $inTransitId)
            ->where('sent_at', '<', now()->subDays(3))
            ->count();

        $overdueInvoices = Sale::query()
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('payment_status', '!=', 'paid')
            ->where('confirmed_at', '<', now()->subDays(30))
            ->count();

        $draftInventories = DB::table('inventories')->where('status', 'draft')->count();
        $pendingExpenses = DB::table('expenses')->where('status', 'pending')->count();

        return response()->json(['data' => [
            ['key' => 'low_stock', 'label' => 'Articles sous le seuil minimal', 'count' => $lowStock, 'severity' => $lowStock > 0 ? 'warn' : 'ok'],
            ['key' => 'below_floor', 'label' => 'Tarifs sous le prix plancher', 'count' => $belowFloor, 'severity' => $belowFloor > 0 ? 'bad' : 'ok'],
            ['key' => 'over_credit', 'label' => 'Clients au-dessus du plafond de crédit', 'count' => $overLimit, 'severity' => $overLimit > 0 ? 'bad' : 'ok'],
            ['key' => 'late_transfers', 'label' => 'Transferts en transit depuis plus de 3 jours', 'count' => $lateTransfers, 'severity' => $lateTransfers > 0 ? 'warn' : 'ok'],
            ['key' => 'overdue_invoices', 'label' => 'Factures impayées depuis plus de 30 jours', 'count' => $overdueInvoices, 'severity' => $overdueInvoices > 0 ? 'warn' : 'ok'],
            ['key' => 'draft_inventories', 'label' => 'Inventaires en attente de validation', 'count' => $draftInventories, 'severity' => $draftInventories > 0 ? 'sky' : 'ok'],
            ['key' => 'pending_expenses', 'label' => 'Charges en attente de validation', 'count' => $pendingExpenses, 'severity' => $pendingExpenses > 0 ? 'sky' : 'ok'],
        ]]);
    }
}
