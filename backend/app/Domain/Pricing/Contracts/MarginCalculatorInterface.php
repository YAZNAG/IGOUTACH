<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Contracts;

interface MarginCalculatorInterface
{
    /**
     * Marge en pourcentage du prix de vente par rapport au coût unitaire.
     * Retourne 0 si le prix est nul.
     */
    public function marginPercent(float $salePrice, float $unitCost): float;

    /**
     * Prix plancher = coût × (1 + marge minimale / 100).
     */
    public function floorPrice(float $unitCost, float $minMarginPercent): float;
}
