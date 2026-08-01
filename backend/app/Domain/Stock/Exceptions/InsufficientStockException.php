<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public static function for(int $warehouseId, int $productId, int $available, int $requested): self
    {
        return new self(sprintf(
            'Stock insuffisant pour l\'article %d dans le lieu %d : %d disponible(s), %d demandé(s).',
            $productId,
            $warehouseId,
            $available,
            $requested,
        ));
    }
}
