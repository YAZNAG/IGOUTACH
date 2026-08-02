<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Crée un bon de commande en brouillon avec numéro auto-incrémenté (BC-YYYY-0001).
 */
final class CreatePurchaseOrderAction
{
    public function execute(
        int $supplierId,
        int $warehouseId,
        ?\DateTime $expectedAt,
        ?string $notes,
        int $createdBy,
        array $lines = []
    ): PurchaseOrder {
        return DB::transaction(function () use ($supplierId, $warehouseId, $expectedAt, $notes, $createdBy, $lines): PurchaseOrder {
            // Récupérer le statut 'draft'
            $draftStatus = PurchaseOrderStatus::where('code', 'draft')->firstOrFail();

            // Générer le numéro (BC-YYYY-0001)
            $number = $this->generateNumber();

            // Créer le bon de commande
            $order = PurchaseOrder::create([
                'number' => $number,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'ordered_at' => now(),
                'expected_at' => $expectedAt,
                'status_id' => $draftStatus->id,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            // Ajouter les lignes
            foreach ($lines as $position => $line) {
                $order->lines()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'received_quantity' => 0,
                    'position' => $position,
                ]);
            }

            return $order;
        });
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $prefix = "BC-{$year}-";

        // Trouver le dernier numéro de cette année
        $lastOrder = PurchaseOrder::where('number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->number, strlen($prefix));
            $newNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$newNumber}";
    }
}
