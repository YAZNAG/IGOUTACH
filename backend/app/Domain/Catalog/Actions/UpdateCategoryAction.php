<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\DTOs\CategoryData;
use App\Domain\Catalog\Models\Category;

final class UpdateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function execute(Category $category, CategoryData $data): Category
    {
        return $this->categories->update($category, $data);
    }
}
