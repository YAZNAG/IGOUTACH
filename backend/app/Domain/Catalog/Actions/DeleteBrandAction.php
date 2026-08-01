<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\BrandInUseException;
use App\Domain\Catalog\Models\Brand;

/**
 * « Suppression » d'une marque = désactivation logique si elle est utilisée.
 */
final class DeleteBrandAction
{
    public function execute(Brand $brand): void
    {
        $count = $brand->products()->count();

        if ($count > 0) {
            throw BrandInUseException::usedByProducts($count);
        }

        $brand->update(['is_active' => false]);
    }
}
