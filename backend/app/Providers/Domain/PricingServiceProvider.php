<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Pricing\Contracts\MarginCalculatorInterface;
use App\Domain\Pricing\Contracts\PriceResolverInterface;
use App\Domain\Pricing\Contracts\ProductPriceRepositoryInterface;
use App\Domain\Pricing\Repositories\ProductPriceRepository;
use App\Domain\Pricing\Services\MarginCalculator;
use App\Domain\Pricing\Services\PriceResolver;
use Illuminate\Support\ServiceProvider;

final class PricingServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ProductPriceRepositoryInterface::class => ProductPriceRepository::class,
        MarginCalculatorInterface::class => MarginCalculator::class,
        PriceResolverInterface::class => PriceResolver::class,
    ];
}
