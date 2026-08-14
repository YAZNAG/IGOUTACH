<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remises de caisse : l'argent qui quitte le lieu pour l'administration.
 *
 * Sans trace écrite, le décompte entre le responsable et la direction se fait
 * de mémoire — c'est là que naissent les désaccords. Une remise est donc
 * déclarée par celui qui donne, puis confirmée par celui qui reçoit : tant
 * qu'elle n'est pas confirmée, chacun voit qu'elle est en route.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_remittances', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            // Rattachement à la session ouverte au moment de la remise. Nul si
            // le lieu remet sans tenir de session de caisse.
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('remitted_at');
            $table->string('status', 20)->default('pending');
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index('remitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_remittances');
    }
};
