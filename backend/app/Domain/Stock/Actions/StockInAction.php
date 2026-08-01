<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Domain\Stock\Models\StockMovement;

final class StockInAction
{
    public function __construct(
        private readonly StockWriterInterface $stock,
    ) {}

    public function execute(StockMovementData $data): StockMovement
    {
        return $this->stock->increase($data);
    }
}
