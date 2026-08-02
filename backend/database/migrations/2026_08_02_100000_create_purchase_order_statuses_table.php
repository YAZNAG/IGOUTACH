<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statuts des bons de commande (lookup table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->timestamps();
        });

        // Seed statuts de base
        \Illuminate\Support\Facades\DB::table('purchase_order_statuses')->insert([
            ['code' => 'draft', 'name' => 'Brouillon', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'sent', 'name' => 'Envoyé', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'pending_approval', 'name' => 'En attente d\'approbation', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'partially_received', 'name' => 'Partiellement reçu', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'received', 'name' => 'Reçu', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'cancelled', 'name' => 'Annulé', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_statuses');
    }
};
