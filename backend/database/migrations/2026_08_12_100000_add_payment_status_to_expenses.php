<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * État de règlement d'une charge : payée sur-le-champ, ou portée au crédit.
 *
 * Le mode de paiement existait déjà mais rien ne disait si la charge avait été
 * réglée : une charge sans mode pouvait aussi bien être payée en espèces sans
 * précision qu'impayée. La colonne tranche.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('expenses', 'payment_status')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table): void {
            // paid → réglée ; unpaid → portée au crédit, reste due.
            $table->string('payment_status', 10)->default('paid')->after('payment_method_id');
            $table->date('paid_at')->nullable()->after('payment_status');
            $table->index(['payment_status', 'expense_date']);
        });

        // Les charges déjà saisies l'ont été sans notion de crédit : elles sont
        // considérées réglées, et datées du jour de la dépense. Les marquer
        // impayées ferait apparaître une dette qui n'a jamais existé.
        DB::table('expenses')->update(['paid_at' => DB::raw('expense_date')]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('expenses', 'payment_status')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex(['payment_status', 'expense_date']);
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
