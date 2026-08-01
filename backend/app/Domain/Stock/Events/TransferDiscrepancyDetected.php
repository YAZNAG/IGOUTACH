<?php

declare(strict_types=1);

namespace App\Domain\Stock\Events;

use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferLine;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Émis lorsqu'une quantité reçue diffère de la quantité envoyée.
 */
final class TransferDiscrepancyDetected
{
    use Dispatchable;

    public function __construct(
        public readonly Transfer $transfer,
        public readonly TransferLine $line,
        public readonly int $sent,
        public readonly int $received,
    ) {}
}
