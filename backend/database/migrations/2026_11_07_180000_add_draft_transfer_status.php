<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Statut « brouillon » des transferts : un bon préparé, modifiable, qui ne
 * touche au stock qu'à l'envoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('transfer_statuses')->updateOrInsert(
            ['code' => 'draft'],
            ['name' => 'Brouillon', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        DB::table('transfer_statuses')->where('code', 'draft')->delete();
    }
};
