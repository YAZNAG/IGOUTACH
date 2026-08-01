<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

final class BrandInUseException extends RuntimeException
{
    public static function usedByProducts(int $count): self
    {
        return new self("Cette marque est rattachée à {$count} article(s). Désactivez-la plutôt que de la supprimer.");
    }
}
