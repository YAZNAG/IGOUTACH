<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Unit;
use Illuminate\Database\Seeder;

final class CatalogSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string}>
     */
    public const UNITS = [
        ['code' => 'pcs', 'name' => 'Pièce'],
        ['code' => 'box', 'name' => 'Carton'],
        ['code' => 'kg', 'name' => 'Kilogramme'],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $unit) {
            Unit::updateOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
