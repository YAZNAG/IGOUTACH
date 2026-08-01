<?php

declare(strict_types=1);

namespace App\Domain\Stock\DTOs;

final readonly class TransferData
{
    /**
     * @param  list<TransferLineData>  $lines
     */
    public function __construct(
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public array $lines,
        public ?int $userId = null,
        public ?string $note = null,
    ) {}
}
