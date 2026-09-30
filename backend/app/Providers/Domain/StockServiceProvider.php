<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Contracts\StockValuationInterface;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\Repositories\StockRepository;
use App\Domain\Stock\Services\PurchaseCostValuation;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use App\Support\Documents\SequentialDocumentNumberGenerator;
use Illuminate\Support\ServiceProvider;

final class StockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Méthode de valorisation : CMUP par défaut. En changer ne touche
        // aucun autre code (Open/Closed + Dependency Inversion).
        // Le stock est valorisé au coût d'achat. La moyenne pondérée
        // (AverageCostValuation) reste dans le domaine pour mémoire, mais
        // plus rien ne l'utilise : elle produisait un second coût que
        // l'application n'affiche plus nulle part.
        $this->app->bind(StockValuationInterface::class, PurchaseCostValuation::class);

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
