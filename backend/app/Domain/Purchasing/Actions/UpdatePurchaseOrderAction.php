<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Modifie un bon de commande en brouillon (remplacement intégral des lignes).
 */
final class UpdatePurchaseOrderAction
{
    /**
     * @param  list<array{product_id: int, quantity: int}>  $lines
     */
    public function execute(
        PurchaseOrder $order,
        int $supplierId,
        int $warehouseId,
        ?\DateTime $expectedAt,
        ?string $notes,
        array $lines
    ): PurchaseOrder {
        return DB::transaction(function () use ($order, $supplierId, $warehouseId, $expectedAt, $notes, $lines): PurchaseOrder {
            if ($order->status()->first()?->code !== 'draft') {
                throw new RuntimeException('Seul un bon de commande en brouillon peut être modifié.');
            }

            $order->update([
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'expected_at' => $expectedAt,
                'notes' => $notes,
            ]);

            // Remplacement intégral des lignes (delete + recreate avec positions).
            $order->lines()->delete();

            foreach ($lines as $position => $line) {
                $order->lines()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'received_quantity' => 0,
                    'position' => $position,
                ]);
            }

            return $order->refresh();
        });
    }
}
