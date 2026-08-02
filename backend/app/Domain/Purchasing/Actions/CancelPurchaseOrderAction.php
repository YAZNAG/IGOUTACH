<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Annule un bon de commande (refuse si réception existante).
 */
final class CancelPurchaseOrderAction
{
    public function execute(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order): PurchaseOrder {
            if (! $order->canCancel()) {
                throw new RuntimeException('Impossible d\'annuler ce bon de commande.');
            }

            // Vérifier s'il y a des articles reçus
            if ($order->lines()->where('received_quantity', '>', 0)->exists()) {
                throw new RuntimeException('Impossible d\'annuler un bon de commande avec des articles reçus.');
            }

            $cancelledStatus = PurchaseOrderStatus::where('code', 'cancelled')->firstOrFail();

            $order->update([
                'status_id' => $cancelledStatus->id,
            ]);

            return $order->refresh();
        });
    }
}
