<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Jeu de données de test complet pour un environnement de démonstration :
 *   php artisan migrate --seed          (référentiels de base)
 *   php artisan db:seed --class=TestDataSeeder
 */
final class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoPricingSeeder::class,      // tarifs détail / demi-gros / gros
            DemoStockSeeder::class,        // stock initial par lieu
            DemoTransactionsSeeder::class, // achats, réceptions, crédits, devis, ventes
        ]);
    }
}
