<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\CategoryInUseException;
use App\Domain\Catalog\Models\Category;

final class DeleteCategoryAction
{
    /**
     * Supprime une catégorie uniquement si elle ne contient aucun article.
     *
     * @throws CategoryInUseException
     */
    public function execute(Category $category): void
    {
        $count = $category->products()->count();

        if ($count > 0) {
            throw CategoryInUseException::hasProducts($count);
        }

        $category->delete();
    }
}
