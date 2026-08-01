<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Marques présentes dans le catalogue client (extraites de igx.xlsx).
 * GENERIQUE = affectation par défaut des articles sans marque identifiable.
 */
final class BrandSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const BRANDS = [
        'ASTRO', 'WDLINK', 'ECHOLINK', 'ECHOSAT', 'REVOLUTION', 'VISION', 'RIFLAND',
        'AZASAT', 'AZATECH', 'TECHNOSTAR', 'ISTAR', 'LINKSTAR', 'NEXT VISION', 'TOPSTAR',
        'DAIKO', 'CHIQ', 'AIWA', 'RECSON', 'EUROMAX', 'SABSAT', 'GREEN', 'WINFORD',
        'BLANDY', 'TENDA', 'JEDEL', 'XLEADER', 'HAVIC', 'ITGURU', 'LOGITECH', 'RAZER',
        'TENGO', 'OVVO', 'LOZA', 'MEIDOU', 'PAVAREAL', 'VIDVIE', 'GERLAX', 'INKAX',
        'BAVIN', 'IP GOLD', 'KAKUSIGA', 'HIBRO', 'JIA YUE', 'NEWAY', 'REDINGTON',
        'DK', 'JBL', 'BEATS', 'SAMSUNG', 'XIAOMI', 'PANASONIC', 'SWISS', 'ALTEC',
        'IGOUTECH', 'GENERIQUE',
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $position => $name) {
            Brand::updateOrCreate(
                ['name' => $name],
                [
                    'code' => $this->code($name),
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }

    private function code(string $name): string
    {
        return Str::upper(Str::of($name)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 20)->value());
    }
}
