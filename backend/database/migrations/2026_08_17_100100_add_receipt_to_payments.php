<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Justificatif photographié d'un encaissement.
 *
 * Un virement ne laisse aucune trace dans le tiroir : le seul élément qui
 * prouve qu'il est arrivé est l'avis de la banque. Pouvoir l'attacher au
 * règlement évite d'avoir à le retrouver dans un téléphone six mois plus tard.
 *
 * Facultatif, et ouvert à tous les modes : le chèque a déjà son portefeuille,
 * mais rien n'interdit de photographier un reçu d'espèces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('receipt_path')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('receipt_path');
        });
    }
};
