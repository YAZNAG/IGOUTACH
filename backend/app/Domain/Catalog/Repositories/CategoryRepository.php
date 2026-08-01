<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\DTOs\CategoryData;
use App\Domain\Catalog\Models\Category;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function create(CategoryData $data): Category
    {
        return Category::create($data->toAttributes());
    }

    public function update(Category $category, CategoryData $data): Category
    {
        $category->update($data->toAttributes());

        return $category->refresh();
    }
}
