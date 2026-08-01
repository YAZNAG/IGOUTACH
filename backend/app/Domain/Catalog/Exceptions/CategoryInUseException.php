<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

final class CategoryInUseException extends RuntimeException
{
    public static function hasProducts(int $count): self
    {
        return new self("Suppression impossible : la catégorie contient {$count} article(s).");
    }
}
