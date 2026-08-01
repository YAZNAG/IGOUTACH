<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\DTOs\CategoryData;
use App\Domain\Catalog\Models\Category;

final class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function execute(CategoryData $data): Category
    {
        return $this->categories->create($data);
    }
}
