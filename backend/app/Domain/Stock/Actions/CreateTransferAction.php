<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\DTOs\TransferData;
use App\Domain\Stock\Exceptions\InvalidTransferException;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferStatus;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Support\Facades\DB;

/**
 * Étape 1 du transfert : le lieu source expédie la marchandise.
 * → statut « en transit », mouvement transfer_out, stock retiré de la source.
 */
final class CreateTransferAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
        private readonly DocumentNumberGeneratorInterface $numbers,
    ) {}

    public function execute(TransferData $data): Transfer
    {
        if ($data->fromWarehouseId === $data->toWarehouseId) {
            throw InvalidTransferException::sameWarehouse();
        }

        if ($data->lines === []) {
            throw InvalidTransferException::emptyLines();
        }

        return DB::transaction(function () use ($data): Transfer {
            $statusId = TransferStatus::where('code', TransferStatus::IN_TRANSIT)->value('id');

            $transfer = Transfer::create([
                'reference' => $this->numbers->next('transfer'),
                'from_warehouse_id' => $data->fromWarehouseId,
                'to_warehouse_id' => $data->toWarehouseId,
                'transfer_status_id' => $statusId,
                'created_by' => $data->userId,
                'sent_at' => now(),
                'note' => $data->note,
            ]);

            foreach ($data->lines as $line) {
                $transfer->lines()->create([
                    'product_id' => $line->productId,
                    'quantity_sent' => $line->quantity,
                    'unit_cost' => (string) $line->unitCost,
                ]);

                // Sortie du stock source. La marchandise n'appartient plus à aucun
                // lieu pendant le transit mais reste comptée dans le stock global.
                $this->stock->decrease(new StockMovementData(
                    warehouseId: $data->fromWarehouseId,
                    productId: $line->productId,
                    quantity: $line->quantity,
                    movementTypeCode: MovementType::TRANSFER_OUT,
                    unitCost: $line->unitCost,
                    referenceType: Transfer::class,
                    referenceId: $transfer->id,
                    userId: $data->userId,
                ));
            }

            return $transfer->load('lines');
        });
    }
}
