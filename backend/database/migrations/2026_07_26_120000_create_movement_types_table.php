<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // in, out, transfer_in, transfer_out, adjustment, return_in, return_out
            $table->string('name');
            // Sens : +1 entrée, -1 sortie, 0 ajustement (signe porté par le mouvement).
            $table->tinyInteger('sign');
            // Le mouvement recalcule-t-il le coût moyen pondéré (CMUP) ?
            $table->boolean('affects_valuation')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_types');
    }
};
