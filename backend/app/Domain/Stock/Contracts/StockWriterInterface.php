<?php

declare(strict_types=1);

namespace App\Domain\Stock\Contracts;

use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Domain\Stock\Models\StockMovement;

interface StockWriterInterface
{
    /**
     * Entrée de stock : incrémente la quantité et journalise un mouvement.
     */
    public function increase(StockMovementData $data): StockMovement;

    /**
     * Sortie de stock : décrémente la quantité et journalise un mouvement.
     *
     * @throws InsufficientStockException
     */
    public function decrease(StockMovementData $data): StockMovement;
}
