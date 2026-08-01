<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Pricing\Models\PriceType;
use Illuminate\Database\Seeder;

final class PriceTypeSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, rank: int, min_quantity: int}>
     */
    public const TYPES = [
        ['code' => 'detail', 'name' => 'Détail', 'rank' => 1, 'min_quantity' => 1],
        ['code' => 'semi_gros', 'name' => 'Demi-gros', 'rank' => 2, 'min_quantity' => 10],
        ['code' => 'gros', 'name' => 'Gros', 'rank' => 3, 'min_quantity' => 50],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            PriceType::updateOrCreate(['code' => $type['code']], [
                ...$type,
                'is_active' => true,
            ]);
        }
    }
}
