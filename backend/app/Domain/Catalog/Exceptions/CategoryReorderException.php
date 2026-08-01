<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

final class CategoryReorderException extends RuntimeException
{
    public static function selfParent(): self
    {
        return new self('Une catégorie ne peut pas être son propre parent.');
    }

    public static function depthExceeded(): self
    {
        return new self('Profondeur maximale de 2 niveaux : le parent ne peut pas être lui-même une sous-catégorie.');
    }

    public static function parentHasChildren(): self
    {
        return new self('Une famille ayant des sous-catégories ne peut pas devenir elle-même une sous-catégorie.');
    }
}
