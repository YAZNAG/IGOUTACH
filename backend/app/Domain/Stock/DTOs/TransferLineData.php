<?php

declare(strict_types=1);

namespace App\Domain\Stock\DTOs;

final readonly class TransferLineData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public float $unitCost = 0.0,
    ) {}
}
