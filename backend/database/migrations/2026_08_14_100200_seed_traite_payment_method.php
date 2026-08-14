<?php

declare(strict_types=1);

use App\Domain\Settings\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;

/**
 * Mode de règlement « Traite ».
 *
 * La traite se saisit comme un chèque — série, date, tireur, banque — mais
 * elle doit d'abord exister comme mode de paiement, sinon rien à l'écran ne
 * permet de la choisir et l'effet resterait inatteignable.
 */
return new class extends Migration
{
    public function up(): void
    {
        PaymentMethod::query()->firstOrCreate(
            ['code' => 'TRAITE'],
            [
                'name' => 'Traite',
                // Même famille que le chèque : un effet remis, encaissé plus
                // tard, susceptible de revenir impayé.
                'type' => 'cheque',
                'is_active' => true,
                'position' => 5,
            ],
        );
    }

    public function down(): void
    {
        PaymentMethod::query()->where('code', 'TRAITE')->delete();
    }
};
