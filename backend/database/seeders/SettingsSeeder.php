<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Settings\Models\DocumentSequence;
use App\Domain\Settings\Models\PaymentMethod;
use Illuminate\Database\Seeder;

final class SettingsSeeder extends Seeder
{
    /**
     * Modes de paiement par défaut.
     *
     * @var array<int, array{code: string, name: string, type: string, position: int}>
     */
    public const PAYMENT_METHODS = [
        ['code' => 'CASH', 'name' => 'Espèces', 'type' => 'cash', 'position' => 1],
        ['code' => 'CHEQUE', 'name' => 'Chèque', 'type' => 'cheque', 'position' => 2],
        ['code' => 'TRANSFER', 'name' => 'Virement', 'type' => 'transfer', 'position' => 3],
        ['code' => 'CARD', 'name' => 'Carte bancaire', 'type' => 'card', 'position' => 4],
    ];

    /**
     * Séquences de numérotation par défaut.
     *
     * @var array<int, array{key: string, prefix: string}>
     */
    public const SEQUENCES = [
        ['key' => 'sale_invoice', 'prefix' => 'FAC-'],
        ['key' => 'delivery_note', 'prefix' => 'BL-'],
        ['key' => 'purchase_order', 'prefix' => 'BC-'],
        ['key' => 'goods_receipt', 'prefix' => 'BR-'],
        ['key' => 'stock_issue', 'prefix' => 'BS-'],
        ['key' => 'transfer', 'prefix' => 'TR-'],
    ];

    public function run(): void
    {
        foreach (self::PAYMENT_METHODS as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                ['name' => $method['name'], 'type' => $method['type'], 'position' => $method['position'], 'is_active' => true],
            );
        }

        foreach (self::SEQUENCES as $sequence) {
            DocumentSequence::updateOrCreate(
                ['key' => $sequence['key']],
                ['prefix' => $sequence['prefix']],
            );
        }
    }
}
