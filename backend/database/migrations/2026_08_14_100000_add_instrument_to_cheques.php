<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue le chèque de la traite (lettre de change).
 *
 * Les deux effets portent exactement les mêmes attributs — numéro de série,
 * date, tireur, banque, montant — et suivent le même cycle : portefeuille,
 * remise, encaissement ou impayé. Une table séparée aurait dupliqué ce cycle
 * et obligé à écrire deux fois chaque état, chaque filtre et chaque rapport.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cheques', 'instrument')) {
            return;
        }

        Schema::table('cheques', function (Blueprint $table): void {
            // 'cheque' | 'traite'
            $table->string('instrument', 10)->default('cheque')->after('id');
            $table->index(['instrument', 'status']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cheques', 'instrument')) {
            return;
        }

        Schema::table('cheques', function (Blueprint $table): void {
            $table->dropIndex(['instrument', 'status']);
            $table->dropColumn('instrument');
        });
    }
};
