<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\Exceptions;

use RuntimeException;

final class WarehouseInUseException extends RuntimeException
{
    public static function stockNotEmpty(int $references, float $value): self
    {
        return new self(sprintf(
            'Désactivation impossible : %d référence(s) en stock pour une valeur de %s DH. Faites un transfert de retour d\'abord.',
            $references,
            number_format($value, 2, ',', ' '),
        ));
    }
}
