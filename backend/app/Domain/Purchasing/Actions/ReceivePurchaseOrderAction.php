<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Réception (totale ou partielle) d'un bon de commande :
 * entrée en stock au prix d'achat (recalcul du CMUP par le socle),
 * mise à jour des reliquats et du dernier prix fournisseur.
 */
final class ReceivePurchaseOrderAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
    ) {}

    /**
     * @param  array<int, int>  $quantities  [purchase_order_line_id => quantité reçue]
     */
    public function execute(PurchaseOrder $order, array $quantities, ?int $userId = null): PurchaseOrder
    {
        if (! in_array($order->status, [PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_PARTIAL], true)) {
            throw new RuntimeException('Seule une commande passée peut être réceptionnée.');
        }

        return DB::transaction(function () use ($order, $quantities, $userId): PurchaseOrder {
            $receivedSomething = false;

            foreach ($order->lines as $line) {
                $received = $quantities[$line->id] ?? 0;
                if ($received <= 0) {
                    continue;
                }

                if ($received > $line->remaining()) {
                    throw new RuntimeException("Quantité reçue supérieure au reliquat (ligne {$line->id}).");
                }

                $this->stock->increase(new StockMovementData(
                    warehouseId: $order->warehouse_id,
                    productId: $line->product_id,
                    quantity: $received,
                    movementTypeCode: 'in',
                    unitCost: (float) $line->unit_price,
                    referenceType: PurchaseOrder::class,
                    referenceId: $order->id,
                    userId: $userId,
                    note: 'Réception '.$order->reference,
                ));

                $line->update(['quantity_received' => $line->quantity_received + $received]);

                // Dernier prix d'achat mémorisé sur le lien article↔fournisseur.
                DB::table('product_supplier')->updateOrInsert(
                    ['product_id' => $line->product_id, 'supplier_id' => $order->supplier_id],
                    ['last_price' => $line->unit_price, 'updated_at' => now(), 'created_at' => now()],
                );

                $receivedSomething = true;
            }

            if (! $receivedSomething) {
                throw new RuntimeException('Aucune quantité à réceptionner.');
            }

            $order->refresh()->load('lines');
            $fullyReceived = $order->lines->every(fn ($l) => $l->remaining() === 0);

            $order->update(['status' => $fullyReceived ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_PARTIAL]);

            return $order->refresh()->load('lines.product:id,sku,name');
        });
    }
}
