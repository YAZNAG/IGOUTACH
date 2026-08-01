<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Autorise les quantités décimales (mètre, kg) vs entières (pièce).
            $table->boolean('is_decimal')->default(false)->after('name');
            $table->unsignedInteger('position')->default(0)->after('is_decimal');
            $table->boolean('is_active')->default(true)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['is_decimal', 'position', 'is_active']);
        });
    }
};
