<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

final class ProductInUseException extends RuntimeException
{
    public static function make(string $reason): self
    {
        return new self("Suppression impossible : {$reason}.");
    }
}
