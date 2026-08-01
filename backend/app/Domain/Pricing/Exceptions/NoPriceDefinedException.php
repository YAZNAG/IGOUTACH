<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Exceptions;

use RuntimeException;

final class NoPriceDefinedException extends RuntimeException
{
    public static function for(int $productId): self
    {
        return new self("Aucun tarif en vigueur pour l'article {$productId}.");
    }
}
