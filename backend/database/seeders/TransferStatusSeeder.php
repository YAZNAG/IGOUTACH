<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Stock\Models\TransferStatus;
use Illuminate\Database\Seeder;

final class TransferStatusSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string}>
     */
    public const STATUSES = [
        ['code' => 'in_transit', 'name' => 'En transit'],
        ['code' => 'received', 'name' => 'Reçu'],
        ['code' => 'cancelled', 'name' => 'Annulé'],
    ];

    public function run(): void
    {
        foreach (self::STATUSES as $status) {
            TransferStatus::updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
