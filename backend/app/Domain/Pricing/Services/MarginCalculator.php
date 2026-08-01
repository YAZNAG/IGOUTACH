<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Domain\Pricing\Contracts\MarginCalculatorInterface;

final class MarginCalculator implements MarginCalculatorInterface
{
    public function marginPercent(float $salePrice, float $unitCost): float
    {
        if ($salePrice <= 0.0) {
            return 0.0;
        }

        return round((($salePrice - $unitCost) / $salePrice) * 100, 2);
    }

    public function floorPrice(float $unitCost, float $minMarginPercent): float
    {
        return round($unitCost * (1 + $minMarginPercent / 100), 2);
    }
}
