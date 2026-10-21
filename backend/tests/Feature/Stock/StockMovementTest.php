<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Actions\StockInAction;
use App\Domain\Stock\Actions\StockOutAction;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;

beforeEach(function () {
    $this->seed(MovementTypeSeeder::class);
    $this->warehouse = Warehouse::factory()->create();
    $this->product = Product::factory()->create();
});

it('incrémente le stock et journalise un mouvement', function () {
    $in = app(StockInAction::class);

    $movement = $in->execute(new StockMovementData(
        warehouseId: $this->warehouse->id,
        productId: $this->product->id,
        quantity: 10,
        movementTypeCode: MovementType::IN,
        unitCost: 100,
    ));

    expect($movement->quantity)->toBe(10)
        ->and($movement->balance_after)->toBe(10);

    $stock = Stock::withoutGlobalScopes()
        ->where('warehouse_id', $this->warehouse->id)
        ->where('product_id', $this->product->id)
        ->first();

    expect($stock->quantity)->toBe(10)
        ->and((float) $stock->average_cost)->toBe(100.0);
});

it('retient le dernier prix d\'achat, pas une moyenne', function () {
    $in = app(StockInAction::class);

    $in->execute(new StockMovementData($this->warehouse->id, $this->product->id, 10, MovementType::IN, 100));
    $in->execute(new StockMovementData($this->warehouse->id, $this->product->id, 10, MovementType::IN, 200));

    $stock = Stock::withoutGlobalScopes()
        ->where('warehouse_id', $this->warehouse->id)
        ->where('product_id', $this->product->id)
        ->first();

    // Le coût moyen pondéré donnait 150 — un chiffre que le commerçant ne
    // reconnaissait sur aucune facture. C'est le dernier prix payé qui fait
    // foi : 200, celui de la dernière entrée.
    expect($stock->quantity)->toBe(20)
        ->and((float) $stock->average_cost)->toBe(200.0);
});

it('décrémente le stock au coût moyen courant', function () {
    app(StockInAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 20, MovementType::IN, 150),
    );

    $movement = app(StockOutAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 5, MovementType::OUT),
    );

    expect($movement->quantity)->toBe(-5)
        ->and($movement->balance_after)->toBe(15)
        ->and((float) $movement->unit_cost)->toBe(150.0);
});

it('refuse une sortie supérieure au stock disponible', function () {
    app(StockInAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 3, MovementType::IN, 100),
    );

    app(StockOutAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 10, MovementType::OUT),
    );
})->throws(InsufficientStockException::class);

it('nomme l\'article et le lieu quand le stock manque', function () {
    app(StockInAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 3, MovementType::IN, 100),
    );

    // Le message portait les identifiants — « l'article 334 dans le lieu 4 » —
    // et le vendeur ne savait pas de quelle ligne de son bon on lui parlait.
    try {
        app(StockOutAction::class)->execute(
            new StockMovementData($this->warehouse->id, $this->product->id, 10, MovementType::OUT),
        );
        test()->fail('Une sortie supérieure au stock aurait dû être refusée.');
    } catch (InsufficientStockException $e) {
        expect($e->getMessage())
            ->toContain($this->product->name)
            ->toContain($this->warehouse->code)
            ->toContain('3 disponible(s), 10 demandé(s)')
            ->not->toContain('#'.$this->product->id);
    }
});

it('conserve un historique append-only des mouvements', function () {
    $in = app(StockInAction::class);
    $in->execute(new StockMovementData($this->warehouse->id, $this->product->id, 5, MovementType::IN, 100));
    $in->execute(new StockMovementData($this->warehouse->id, $this->product->id, 5, MovementType::IN, 100));
    app(StockOutAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 2, MovementType::OUT),
    );

    expect(StockMovement::withoutGlobalScopes()->count())->toBe(3);
});

it('lit la quantité via le StockReaderInterface', function () {
    app(StockInAction::class)->execute(
        new StockMovementData($this->warehouse->id, $this->product->id, 7, MovementType::IN, 100),
    );

    $reader = app(StockReaderInterface::class);

    expect($reader->quantityFor($this->warehouse->id, $this->product->id))->toBe(7)
        ->and($reader->globalQuantityFor($this->product->id))->toBe(7);
});
