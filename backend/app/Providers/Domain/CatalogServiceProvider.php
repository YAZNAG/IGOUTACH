<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Repositories\CategoryRepository;
use App\Domain\Catalog\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ProductRepositoryInterface::class => ProductRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
    ];
}
