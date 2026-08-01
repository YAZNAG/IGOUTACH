<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Contracts;

use App\Domain\Pricing\DTOs\ResolvedPrice;
use App\Domain\Pricing\Exceptions\NoPriceDefinedException;
use Carbon\CarbonInterface;

interface PriceResolverInterface
{
    /**
     * Résout le prix applicable pour un article selon la quantité (et le
     * client si fourni), à une date donnée.
     *
     * @throws NoPriceDefinedException
     */
    public function resolve(
        int $productId,
        int $quantity,
        ?int $customerId = null,
        ?CarbonInterface $at = null,
    ): ResolvedPrice;
}
