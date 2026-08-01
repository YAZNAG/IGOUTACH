<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

final class UnitInUseException extends RuntimeException
{
    public static function usedByProducts(int $count): self
    {
        return new self("Cette unité est utilisée par {$count} article(s). Désactivez-la plutôt que de la supprimer.");
    }

    public static function decimalMovementsExist(): self
    {
        return new self('Impossible de passer cette unité en non décimale : des mouvements de stock à quantité décimale existent.');
    }
}
