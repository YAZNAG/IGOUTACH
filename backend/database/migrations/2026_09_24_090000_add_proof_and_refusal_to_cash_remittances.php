<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le transfert de caisse gagne une preuve et un refus.
 *
 * Une photo (reçu signé, billets comptés, bordereau) parce que le montant
 * déclaré et le montant reçu se discutent : une image jointe à la déclaration
 * ferme la discussion avant qu'elle ne s'ouvre.
 *
 * Un refus parce que la direction n'a jusqu'ici que deux issues — confirmer,
 * ou laisser en attente indéfiniment. Ce qu'elle n'a pas reçu doit pouvoir
 * être dit, avec son motif, et rester dans l'historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_remittances', function (Blueprint $table): void {
            $table->string('proof_path', 255)->nullable()->after('note');
            $table->foreignId('refused_by')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
            $table->timestamp('refused_at')->nullable()->after('refused_by');
            $table->string('refusal_reason', 255)->nullable()->after('refused_at');
        });
    }

    public function down(): void
    {
        Schema::table('cash_remittances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('refused_by');
            $table->dropColumn(['proof_path', 'refused_at', 'refusal_reason']);
        });
    }
};
