<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Alertes opérationnelles.
 *
 * Deux portées : les alertes du lieu (visibles dès `stock.view`, limitées au
 * lieu rattaché à l'utilisateur) et les indicateurs transverses, réservés à
 * `report.consolidated`. Un responsable de lieu voit donc ce qui le concerne
 * sans accéder aux chiffres des autres lieux ni aux marges.
 */
final class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $global = $user !== null && $user->can('report.consolidated');
        $warehouseId = $global ? null : ($user?->getAttribute('warehouse_id'));

        $alerts = [];

        // ── Alertes du lieu ────────────────────────────────────────────
        $lowStock = DB::query()->fromSub(
            DB::table('products')
                ->leftJoin('stocks', function ($join) use ($warehouseId): void {
                    $join->on('stocks.product_id', '=', 'products.id');
                    if ($warehouseId !== null) {
                        $join->where('stocks.warehouse_id', '=', $warehouseId);
                    }
                })
                ->whereNotNull('products.min_stock')
                ->where('products.min_stock', '>', 0)
                ->whereNull('products.deleted_at')
                ->groupBy('products.id')
                ->havingRaw('COALESCE(SUM(stocks.quantity), 0) < MIN(products.min_stock)')
                ->select('products.id'),
            'sous_seuil',
        )->count();

        $alerts[] = [
            'key' => 'low_stock',
            'label' => 'Articles sous le seuil minimal',
            'count' => $lowStock,
            'severity' => $lowStock > 0 ? 'warn' : 'ok',
        ];

        $inTransitId = TransferStatus::query()->where('code', TransferStatus::IN_TRANSIT)->value('id');
        $lateTransfers = Transfer::query()
            ->where('transfer_status_id', $inTransitId)
            ->where('sent_at', '<', now()->subDays(3))
            ->when($warehouseId !== null, fn ($q) => $q->where(function ($sub) use ($warehouseId): void {
                $sub->where('from_warehouse_id', $warehouseId)
                    ->orWhere('to_warehouse_id', $warehouseId);
            }))
            ->count();

        $alerts[] = [
            'key' => 'late_transfers',
            'label' => 'Transferts en transit depuis plus de 3 jours',
            'count' => $lateTransfers,
            'severity' => $lateTransfers > 0 ? 'warn' : 'ok',
        ];

        // Sale, Inventory et Expense portent déjà le cloisonnement par lieu.
        $overdueInvoices = Sale::query()
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('payment_status', '!=', 'paid')
            ->where('confirmed_at', '<', now()->subDays(30))
            ->count();

        $alerts[] = [
            'key' => 'overdue_invoices',
            'label' => 'Factures impayées depuis plus de 30 jours',
            'count' => $overdueInvoices,
            'severity' => $overdueInvoices > 0 ? 'warn' : 'ok',
        ];

        $draftInventories = DB::table('inventories')
            ->where('status', 'draft')
            ->when($warehouseId !== null, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->count();

        $alerts[] = [
            'key' => 'draft_inventories',
            'label' => 'Inventaires en attente de validation',
            'count' => $draftInventories,
            'severity' => $draftInventories > 0 ? 'sky' : 'ok',
        ];

        $pendingExpenses = DB::table('expenses')
            ->where('status', 'pending')
            ->when($warehouseId !== null, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->count();

        $alerts[] = [
            'key' => 'pending_expenses',
            'label' => 'Charges en attente de validation',
            'count' => $pendingExpenses,
            'severity' => $pendingExpenses > 0 ? 'sky' : 'ok',
        ];

        // Charges fixes échues et non réglées. Elles restent signalées tant
        // qu'elles ne sont pas payées : c'est tout l'objet du suivi.
        $echeances = DB::table('recurring_expense_occurrences as o')
            ->join('recurring_expenses as c', 'c.id', '=', 'o.recurring_expense_id')
            ->where('o.status', 'pending')
            ->whereDate('o.due_date', '<=', now()->toDateString())
            // Les charges de société (sans lieu) concernent tout le monde.
            ->when($warehouseId !== null, fn ($q) => $q->where(function ($sub) use ($warehouseId): void {
                $sub->where('c.warehouse_id', $warehouseId)->orWhereNull('c.warehouse_id');
            }))
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(o.amount), 0) as montant')
            ->first();

        $nbEcheances = (int) ($echeances?->nb ?? 0);

        $alerts[] = [
            'key' => 'recurring_expenses_due',
            'label' => 'Charges fixes à régler',
            'count' => $nbEcheances,
            'amount' => round((float) ($echeances?->montant ?? 0), 2),
            'severity' => $nbEcheances > 0 ? 'bad' : 'ok',
            'link' => '/charges-fixes',
        ];

        // ── Indicateurs transverses (direction uniquement) ─────────────
        if ($global) {
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

            $alerts[] = [
                'key' => 'below_floor',
                'label' => 'Tarifs sous le prix plancher',
                'count' => $belowFloor,
                'severity' => $belowFloor > 0 ? 'bad' : 'ok',
            ];
            $alerts[] = [
                'key' => 'over_credit',
                'label' => 'Clients au-dessus du plafond de crédit',
                'count' => $overLimit,
                'severity' => $overLimit > 0 ? 'bad' : 'ok',
            ];
        }

        return response()->json([
            'data' => $alerts,
            'scope' => $global ? 'global' : 'warehouse',
        ]);
    }
}
