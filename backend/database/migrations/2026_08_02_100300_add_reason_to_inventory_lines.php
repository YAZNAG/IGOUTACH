<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventaires : motif d'écart obligatoire sur les lignes en écart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_lines', function (Blueprint $table): void {
            $table->string('reason')->nullable()->after('difference');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_lines', function (Blueprint $table): void {
            $table->dropColumn('reason');
        });
    }
};
