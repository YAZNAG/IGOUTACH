<?php

declare(strict_types=1);

namespace App\Domain\Stock\Services;

use App\Domain\Stock\Contracts\StockValuationInterface;

/**
 * Valorisation au coût moyen unitaire pondéré (CMUP), méthode par défaut (brief §16.3).
 */
final class AverageCostValuation implements StockValuationInterface
{
    public function newUnitCost(
        int $currentQty,
        float $currentCost,
        int $incomingQty,
        float $incomingCost,
    ): float {
        $totalQty = $currentQty + $incomingQty;

        if ($totalQty <= 0) {
            return round($incomingCost, 2);
        }

        $value = ($currentQty * $currentCost) + ($incomingQty * $incomingCost);

        return round($value / $totalQty, 2);
    }
}
