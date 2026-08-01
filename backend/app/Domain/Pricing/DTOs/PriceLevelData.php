<?php

declare(strict_types=1);

namespace App\Domain\Pricing\DTOs;

/**
 * Un niveau de prix à enregistrer (detail, demi-gros ou gros).
 */
final readonly class PriceLevelData
{
    public function __construct(
        public string $priceTypeCode,
        public float $amount,
        public float $minMarginPercent = 0.0,
        public ?int $minQuantity = null,
    ) {}
}
