<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit — append-only : aucune ligne n'est jamais modifiée ni supprimée.
 * Chaque action sensible (accès, sécurité, stock…) y laisse une trace horodatée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();          // ex. user.created, session.revoked
            $table->string('module')->nullable()->index();
            $table->string('entity_type')->nullable();   // ex. App\Models\User
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('description')->nullable();
            $table->json('changes')->nullable();         // avant/après
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
