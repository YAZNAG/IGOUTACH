<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exceptions;

use RuntimeException;

final class InvalidTransferException extends RuntimeException
{
    public static function sameWarehouse(): self
    {
        return new self('Les lieux source et destination doivent être différents.');
    }

    public static function notInTransit(): self
    {
        return new self('Seul un transfert en transit peut être réceptionné.');
    }

    public static function emptyLines(): self
    {
        return new self('Un transfert doit comporter au moins une ligne.');
    }
}
