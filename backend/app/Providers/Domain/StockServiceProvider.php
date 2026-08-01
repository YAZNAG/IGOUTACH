<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Contracts\StockValuationInterface;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\Repositories\StockRepository;
use App\Domain\Stock\Services\AverageCostValuation;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use App\Support\Documents\SequentialDocumentNumberGenerator;
use Illuminate\Support\ServiceProvider;

final class StockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Méthode de valorisation : CMUP par défaut. En changer ne touche
        // aucun autre code (Open/Closed + Dependency Inversion).
        $this->app->bind(StockValuationInterface::class, AverageCostValuation::class);

        $this->app->bind(
            DocumentNumberGeneratorInterface::class,
            SequentialDocumentNumberGenerator::class,
        );

        // Reader et Writer partagent la même implémentation (une seule instance).
        $this->app->singleton(StockRepository::class);
        $this->app->bind(StockReaderInterface::class, StockRepository::class);
        $this->app->bind(StockWriterInterface::class, StockRepository::class);
    }
}
