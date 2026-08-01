<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\UnitInUseException;
use App\Domain\Catalog\Models\Unit;

/**
 * « Suppression » d'une unité = désactivation logique.
 * Bloquée si l'unité est encore rattachée à des articles.
 */
final class DeleteUnitAction
{
    public function execute(Unit $unit): void
    {
        $count = $unit->products()->count();

        if ($count > 0) {
            throw UnitInUseException::usedByProducts($count);
        }

        $unit->update(['is_active' => false]);
    }
}
