<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tous les PDF passent par cette enveloppe : l'arabe (noms de clients,
        // libellés) y est lié et remis dans le bon sens.
        $this->app->bind('dompdf.wrapper', fn ($app) => new \App\Support\Pdf\ArabicAwarePdf(
            $app['dompdf'], $app['config'], $app['files'], $app['view'],
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compatibilité index utf8mb4 sur anciens serveurs MySQL/MariaDB.
        Schema::defaultStringLength(191);
    }
}
