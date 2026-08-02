<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Expenses\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

/**
 * Catégories de charges par défaut.
 */
final class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Loyer', 'Carburant', 'Fournitures', 'Entretien', 'Divers'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name]);
        }
    }
}
