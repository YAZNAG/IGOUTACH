<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\TaxRate;
use Illuminate\Database\Seeder;

/**
 * Taux de TVA en vigueur au Maroc. Paramétrables ensuite via l'écran Paramètres.
 */
final class TaxRateSeeder extends Seeder
{
    /**
     * @var array<int, array{rate: float, label: string, is_default: bool}>
     */
    public const RATES = [
        ['rate' => 0.0, 'label' => 'Exonéré', 'is_default' => false],
        ['rate' => 7.0, 'label' => 'Taux super réduit', 'is_default' => false],
        ['rate' => 10.0, 'label' => 'Taux réduit', 'is_default' => false],
        ['rate' => 14.0, 'label' => 'Taux intermédiaire', 'is_default' => false],
        ['rate' => 20.0, 'label' => 'Taux normal', 'is_default' => true],
    ];

    public function run(): void
    {
        foreach (self::RATES as $position => $rate) {
            TaxRate::updateOrCreate(
                ['rate' => $rate['rate']],
                [
                    'label' => $rate['label'],
                    'is_default' => $rate['is_default'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }
}
