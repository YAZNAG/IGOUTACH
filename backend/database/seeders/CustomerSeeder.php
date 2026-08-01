<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Seeder;

final class CustomerSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, is_company: bool, city: string, credit_limit: float}>
     */
    public const CUSTOMERS = [
        ['code' => 'C-0001', 'name' => 'Électro Sud SARL', 'is_company' => true, 'city' => 'Agadir', 'credit_limit' => 20000.0],
        ['code' => 'C-0002', 'name' => 'Ahmed Benali', 'is_company' => false, 'city' => 'Casablanca', 'credit_limit' => 5000.0],
        ['code' => 'C-0003', 'name' => 'Comptoir du Nord', 'is_company' => true, 'city' => 'Tanger', 'credit_limit' => 0.0],
    ];

    public function run(): void
    {
        foreach (self::CUSTOMERS as $customer) {
            Customer::updateOrCreate(
                ['code' => $customer['code']],
                [
                    'name' => $customer['name'],
                    'is_company' => $customer['is_company'],
                    'city' => $customer['city'],
                    'credit_limit' => $customer['credit_limit'],
                    'is_active' => true,
                ],
            );
        }
    }
}
