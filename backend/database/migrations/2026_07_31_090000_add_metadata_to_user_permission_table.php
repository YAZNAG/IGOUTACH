<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_permission', function (Blueprint $table) {
            $table->foreignId('granted_by')->nullable()->after('is_granted')->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable()->after('granted_by');
            // Dérogation temporaire : NULL = permanente, sinon se referme d'elle-même.
            $table->timestamp('expires_at')->nullable()->after('reason')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('user_permission', function (Blueprint $table) {
            $table->dropConstrainedForeignId('granted_by');
            $table->dropColumn(['reason', 'expires_at', 'created_at', 'updated_at']);
        });
    }
};
