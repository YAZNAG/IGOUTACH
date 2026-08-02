<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Envoie un bon de commande : brouillon → sent (ou pending_approval si approx paramétré).
 */
final class SendPurchaseOrderAction
{
    public function execute(PurchaseOrder $order, bool $requireApproval = false): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $requireApproval): PurchaseOrder {
            if (! $order->canSend()) {
                throw new RuntimeException('Seul un bon en brouillon peut être envoyé.');
            }

            $targetStatusCode = $requireApproval ? 'pending_approval' : 'sent';
            $newStatus = PurchaseOrderStatus::where('code', $targetStatusCode)->firstOrFail();

            $order->update([
                'status_id' => $newStatus->id,
                'ordered_at' => now(),
            ]);

            return $order->refresh();
        });
    }
}
