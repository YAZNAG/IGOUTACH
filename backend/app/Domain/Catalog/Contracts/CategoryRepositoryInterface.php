<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\DTOs\CategoryData;
use App\Domain\Catalog\Models\Category;

interface CategoryRepositoryInterface
{
    public function create(CategoryData $data): Category;

    public function update(Category $category, CategoryData $data): Category;
}
