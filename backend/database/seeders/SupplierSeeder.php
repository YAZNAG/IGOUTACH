<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Database\Seeder;

final class SupplierSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, city: string, payment_terms_days: int}>
     */
    public const SUPPLIERS = [
        ['code' => 'F-0001', 'name' => 'Global Import Électronique', 'city' => 'Casablanca', 'payment_terms_days' => 30],
        ['code' => 'F-0002', 'name' => 'Maghreb Satellite', 'city' => 'Casablanca', 'payment_terms_days' => 60],
        ['code' => 'F-0003', 'name' => 'Sud Accessoires', 'city' => 'Agadir', 'payment_terms_days' => 0],
    ];

    public function run(): void
    {
        foreach (self::SUPPLIERS as $supplier) {
            Supplier::updateOrCreate(
                ['code' => $supplier['code']],
                [
                    'name' => $supplier['name'],
                    'city' => $supplier['city'],
                    'payment_terms_days' => $supplier['payment_terms_days'],
                    'is_active' => true,
                ],
            );
        }
    }
}
