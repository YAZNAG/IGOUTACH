<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTOs;

/**
 * Tarifs d'un article (écran « Tarifs de vente »).
 */
final readonly class PricingData
{
    public function __construct(
        public float $salePrice,
        public ?float $costPrice = null,
        public float $taxRate = 0.0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            salePrice: (float) $data['sale_price'],
            costPrice: isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            taxRate: (float) ($data['tax_rate'] ?? 0.0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return array_filter([
            'sale_price' => $this->salePrice,
            'cost_price' => $this->costPrice,
            'tax_rate' => $this->taxRate,
        ], static fn ($v) => $v !== null);
    }
}
