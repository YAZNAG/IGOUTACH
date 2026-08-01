<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Stock\Models\MovementType;
use Illuminate\Database\Seeder;

final class MovementTypeSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, sign: int, affects_valuation: bool}>
     */
    public const TYPES = [
        ['code' => 'in', 'name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true],
        ['code' => 'out', 'name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false],
        ['code' => 'transfer_in', 'name' => 'Entrée par transfert', 'sign' => 1, 'affects_valuation' => true],
        ['code' => 'transfer_out', 'name' => 'Sortie par transfert', 'sign' => -1, 'affects_valuation' => false],
        ['code' => 'adjustment', 'name' => 'Ajustement', 'sign' => 0, 'affects_valuation' => false],
        ['code' => 'return_in', 'name' => 'Retour entrant', 'sign' => 1, 'affects_valuation' => true],
        ['code' => 'return_out', 'name' => 'Retour sortant', 'sign' => -1, 'affects_valuation' => false],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            MovementType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
