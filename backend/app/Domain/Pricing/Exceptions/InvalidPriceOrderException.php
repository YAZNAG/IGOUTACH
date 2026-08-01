<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Exceptions;

use RuntimeException;

final class InvalidPriceOrderException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Ordre des prix invalide : gros ≤ demi-gros ≤ détail est obligatoire.');
    }
}
