<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Actions\CreateTransferAction;
use App\Domain\Stock\Actions\ReceiveTransferAction;
use App\Domain\Stock\Actions\StockInAction;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\DTOs\TransferData;
use App\Domain\Stock\DTOs\TransferLineData;
use App\Domain\Stock\Events\TransferDiscrepancyDetected;
use App\Domain\Stock\Exceptions\InvalidTransferException;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\TransferStatus;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;
use Database\Seeders\TransferStatusSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed(MovementTypeSeeder::class);
    $this->seed(TransferStatusSeeder::class);
    $this->source = Warehouse::factory()->create();
    $this->dest = Warehouse::factory()->create();
    $this->product = Product::factory()->create();

    // Approvisionne le lieu source.
    app(StockInAction::class)->execute(
        new StockMovementData($this->source->id, $this->product->id, 20, MovementType::IN, 100),
    );
});

it('expédie le stock et met le transfert en transit', function () {
    $transfer = app(CreateTransferAction::class)->execute(new TransferData(
        fromWarehouseId: $this->source->id,
        toWarehouseId: $this->dest->id,
        lines: [new TransferLineData($this->product->id, 5, 100)],
    ));

    $reader = app(StockReaderInterface::class);

    expect($transfer->status->code)->toBe(TransferStatus::IN_TRANSIT)
        ->and($reader->quantityFor($this->source->id, $this->product->id))->toBe(15)
        ->and($reader->quantityFor($this->dest->id, $this->product->id))->toBe(0);
});

it('maintient le stock global constant pendant le transit', function () {
    $reader = app(StockReaderInterface::class);
    $before = $reader->globalQuantityFor($this->product->id);

    app(CreateTransferAction::class)->execute(new TransferData(
        fromWarehouseId: $this->source->id,
        toWarehouseId: $this->dest->id,
        lines: [new TransferLineData($this->product->id, 5, 100)],
    ));

    // 15 en source + 5 en transit = 20 : inchangé.
    expect($reader->globalQuantityFor($this->product->id))->toBe($before);
});

it('crédite la destination à la réception', function () {
    $transfer = app(CreateTransferAction::class)->execute(new TransferData(
        fromWarehouseId: $this->source->id,
        toWarehouseId: $this->dest->id,
        lines: [new TransferLineData($this->product->id, 5, 100)],
    ));

    $received = app(ReceiveTransferAction::class)->execute($transfer);
    $reader = app(StockReaderInterface::class);

    expect($received->status->code)->toBe(TransferStatus::RECEIVED)
        ->and($reader->quantityFor($this->dest->id, $this->product->id))->toBe(5)
        ->and($reader->quantityFor($this->source->id, $this->product->id))->toBe(15)
        ->and($reader->globalQuantityFor($this->product->id))->toBe(20);
});

it('enregistre et notifie un écart de réception', function () {
    Event::fake([TransferDiscrepancyDetected::class]);

    $transfer = app(CreateTransferAction::class)->execute(new TransferData(
        fromWarehouseId: $this->source->id,
        toWarehouseId: $this->dest->id,
        lines: [new TransferLineData($this->product->id, 5, 100)],
    ));

    $line = $transfer->lines->first();
    $received = app(ReceiveTransferAction::class)->execute($transfer, [$line->id => 3]);

    expect($received->lines->first()->quantity_received)->toBe(3);
    Event::assertDispatched(TransferDiscrepancyDetected::class);
});

it('interdit un transfert vers le même lieu', function () {
    app(CreateTransferAction::class)->execute(new TransferData(
        fromWarehouseId: $this->source->id,
        toWarehouseId: $this->source->id,
        lines: [new TransferLineData($this->product->id, 1, 100)],
    ));
})->throws(InvalidTransferException::class);

it('interdit de réceptionner deux fois un transfert', function () {
    $transfer = app(CreateTransferAction::class)->execute(new TransferData(
        fromWarehouseId: $this->source->id,
        toWarehouseId: $this->dest->id,
        lines: [new TransferLineData($this->product->id, 5, 100)],
    ));

    app(ReceiveTransferAction::class)->execute($transfer);
    app(ReceiveTransferAction::class)->execute($transfer->refresh());
})->throws(InvalidTransferException::class);
