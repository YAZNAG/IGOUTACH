<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Approuve un bon de commande : pending_approval → sent.
 */
final class ApprovePurchaseOrderAction
{
    public function execute(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order): PurchaseOrder {
            if (! $order->canApprove()) {
                throw new RuntimeException('Seul un bon en attente d\'approbation peut être approuvé.');
            }

            $sentStatus = PurchaseOrderStatus::where('code', 'sent')->firstOrFail();

            $order->update([
                'status_id' => $sentStatus->id,
                'ordered_at' => now(),
            ]);

            return $order->refresh();
        });
    }
}
