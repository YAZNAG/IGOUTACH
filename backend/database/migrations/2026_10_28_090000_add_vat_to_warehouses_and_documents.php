<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La TVA entre dans l'application, portée par le lieu.
 *
 * Jusqu'ici tout était hors taxe : ni vente ni réception ne calculait la
 * moindre TVA, et le taux posé sur l'article ne servait qu'à titre indicatif
 * sur sa fiche. Un commerce qui facture avec TVA ne pouvait pas tenir ses
 * ventes ici.
 *
 * Le taux est attaché au lieu, pas au document : un dépôt déclaré à 20 %
 * facture à 20 %, les autres continuent sans rien. Le stock reste séparé
 * comme pour n'importe quel lieu, et aucun document existant ne change —
 * taux à 0, TVA à 0.
 *
 * Deux façons de saisir, selon la maison : prix hors taxe auxquels la TVA
 * s'ajoute, ou prix tout compris dont on extrait la TVA. Le second cas existe
 * dès qu'on affiche au client un prix qui ne doit pas bouger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->decimal('vat_rate', 5, 2)->default(0)->after('is_active');
            $table->boolean('prices_include_vat')->default(false)->after('vat_rate');
        });

        Schema::table('sales', function (Blueprint $table): void {
            // Le taux retenu au moment de la vente : un changement de taux ne
            // doit pas réécrire les factures déjà émises.
            $table->decimal('tax_rate', 5, 2)->default(0)->after('total');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('invoice_number');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->dropColumn(['vat_rate', 'prices_include_vat']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });
    }
};
