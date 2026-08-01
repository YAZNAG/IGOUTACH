<?php

declare(strict_types=1);

namespace App\Domain\Stock\Contracts;

interface StockValuationInterface
{
    /**
     * Calcule le nouveau coût unitaire de stock après une entrée.
     *
     * @param  int  $currentQty  quantité en stock avant l'entrée
     * @param  float  $currentCost  coût unitaire courant
     * @param  int  $incomingQty  quantité entrante (> 0)
     * @param  float  $incomingCost  coût unitaire de l'entrée
     */
    public function newUnitCost(
        int $currentQty,
        float $currentCost,
        int $incomingQty,
        float $incomingCost,
    ): float;
}
