<?php

declare(strict_types=1);

namespace App\Domain\Pricing\DTOs;

final readonly class ResolvedPrice
{
    public function __construct(
        public float $amount,
        public int $priceTypeId,
        public string $priceTypeCode,
        public string $reason,
    ) {}
}
