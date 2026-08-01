<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Events\TransferDiscrepancyDetected;
use App\Domain\Stock\Exceptions\InvalidTransferException;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferStatus;
use Illuminate\Support\Facades\DB;

/**
 * Étape 2 du transfert : le lieu destination valide la réception.
 * → statut « reçu », mouvement transfer_in, stock ajouté à la destination.
 * Tout écart entre quantité envoyée et reçue est enregistré et notifié.
 */
final class ReceiveTransferAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
    ) {}

    /**
     * @param  array<int, int>  $receivedQuantities  [transfer_line_id => quantité reçue] ; défaut = quantité envoyée
     */
    public function execute(Transfer $transfer, array $receivedQuantities = [], ?int $userId = null): Transfer
    {
        $inTransitId = TransferStatus::where('code', TransferStatus::IN_TRANSIT)->value('id');

        if ($transfer->transfer_status_id !== $inTransitId) {
            throw InvalidTransferException::notInTransit();
        }

        return DB::transaction(function () use ($transfer, $receivedQuantities, $userId): Transfer {
            $receivedStatusId = TransferStatus::where('code', TransferStatus::RECEIVED)->value('id');

            foreach ($transfer->lines as $line) {
                $received = $receivedQuantities[$line->id] ?? $line->quantity_sent;

                $line->update(['quantity_received' => $received]);

                if ($received > 0) {
                    $this->stock->increase(new StockMovementData(
                        warehouseId: $transfer->to_warehouse_id,
                        productId: $line->product_id,
                        quantity: $received,
                        movementTypeCode: MovementType::TRANSFER_IN,
                        unitCost: (float) $line->unit_cost,
                        referenceType: Transfer::class,
                        referenceId: $transfer->id,
                        userId: $userId,
                    ));
                }

                if ($received !== $line->quantity_sent) {
                    TransferDiscrepancyDetected::dispatch($transfer, $line, $line->quantity_sent, $received);
                }
            }

            $transfer->update([
                'transfer_status_id' => $receivedStatusId,
                'received_by' => $userId,
                'received_at' => now(),
            ]);

            return $transfer->refresh()->load('lines');
        });
    }
}
