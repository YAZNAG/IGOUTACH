<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Politique de sécurité : compteur de tentatives échouées + verrouillage temporaire
 * du compte après trop d'échecs de connexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('failed_attempts')->default(0)->after('is_active');
            $table->timestamp('locked_until')->nullable()->after('failed_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['failed_attempts', 'locked_until']);
        });
    }
};
