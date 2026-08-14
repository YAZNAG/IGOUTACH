<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ventilation d'un règlement sur les factures qu'il solde.
 *
 * Jusqu'ici un encaissement portait au plus UNE facture (`payments.sale_id`),
 * ou aucune — il tombait alors dans l'encours global. Le client règle en
 * pratique plusieurs factures d'un seul versement, parfois partiellement :
 * sans ventilation, impossible de dire laquelle est soldée et laquelle reste
 * due, et les relances portaient sur un encours indistinct.
 *
 * `payments.sale_id` est conservé pour l'historique et reste renseigné quand
 * le règlement ne vise qu'une facture : les écrans et rapports existants
 * continuent de fonctionner sans réécriture.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_allocations')) {
            return;
        }

        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            // Une facture n'apparaît qu'une fois par règlement : deux lignes
            // pour le même couple rendraient le solde ambigu.
            $table->unique(['payment_id', 'sale_id']);
            $table->index('sale_id');
        });

        // Les règlements déjà affectés à une facture sont repris tels quels,
        // sinon l'historique paraîtrait non ventilé alors qu'il l'était.
        DB::statement(<<<'SQL'
            INSERT INTO payment_allocations (payment_id, sale_id, amount, created_at, updated_at)
            SELECT id, sale_id, amount, NOW(), NOW()
            FROM payments
            WHERE sale_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
