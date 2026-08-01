<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Unit;
use Illuminate\Database\Seeder;

final class UnitSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, is_decimal: bool}>
     */
    public const UNITS = [
        ['code' => 'PCE', 'name' => 'Pièce', 'is_decimal' => false],
        ['code' => 'BTE', 'name' => 'Boîte', 'is_decimal' => false],
        ['code' => 'CTN', 'name' => 'Carton', 'is_decimal' => false],
        ['code' => 'LOT', 'name' => 'Lot', 'is_decimal' => false],
        ['code' => 'MTR', 'name' => 'Mètre', 'is_decimal' => true],
        ['code' => 'KG', 'name' => 'Kilogramme', 'is_decimal' => true],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $position => $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'is_decimal' => $unit['is_decimal'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }
}
