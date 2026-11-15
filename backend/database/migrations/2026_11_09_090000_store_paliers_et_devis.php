<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paliers de prix et demandes de devis sur la boutique.
 *
 * La boutique ne vendait qu'au detail : elle lisait le seul tarif « detail »
 * et ignorait les paliers demi-gros et gros, pourtant deja renseignes pour
 * 459 articles, avec leurs seuils de quantite. Deux manques a combler :
 *
 *  1. la ligne de commande doit dire a quel palier elle a ete vendue, sinon
 *     la vente de gestion creee a la livraison ment sur son tarif ;
 *  2. au-dela d'un palier, la vente ne se conclut plus en ligne mais par un
 *     devis : le client demande, la boutique chiffre, le client accepte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_order_lines', function (Blueprint $table): void {
            // Palier applique : detail, semi_gros, gros. Fige a la commande.
            $table->string('price_type_code', 20)->nullable()->after('unit_price');
        });

        Schema::create('store_quotes', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 20)->unique();
            // requested -> quoted -> accepted | declined | expired | cancelled
            $table->string('status', 12)->default('requested');
            // Le lien que le client recoit : il vaut authentification.
            $table->string('public_token', 64)->unique();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 30);
            $table->string('customer_email', 190)->nullable();
            $table->string('company', 150)->nullable();
            $table->string('city', 120);
            $table->string('address', 255)->nullable();
            $table->string('note', 1000)->nullable();
            // Mot de la boutique au client, visible sur le devis chiffre.
            $table->string('reply', 1000)->nullable();
            $table->string('decline_reason', 255)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            // Le devis de gestion (sales.type = quote) cree a l'envoi.
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('customer_phone');
        });

        Schema::create('store_quote_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('store_quotes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('name', 191);
            $table->string('sku', 60)->nullable();
            $table->unsignedInteger('quantity');
            // Prix indicatif a la demande, puis prix chiffre par la boutique.
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('price_type_code', 20)->nullable();
            $table->decimal('line_total', 12, 2)->default(0);
            // Stock disponible au moment de la demande, pour eclairer la reponse.
            $table->integer('stock_at_request')->nullable();
            $table->timestamps();
        });

        Schema::table('store_orders', function (Blueprint $table): void {
            // La commande nee d'un devis accepte.
            $table->foreignId('quote_id')->nullable()->after('sale_id')->constrained('store_quotes')->nullOnDelete();
        });

        $defauts = [
            // A partir de quel palier la vente passe par un devis :
            // none (jamais), semi_gros, gros.
            'quote_from' => 'semi_gros',
            // Afficher publiquement la grille des paliers sur la fiche.
            'tiers_public' => '1',
            'quote_validity_days' => '7',
            'quote_intro' => 'Dites-nous les quantités souhaitées : nous revenons vers vous avec un prix ferme sous 24 h ouvrées.',
        ];
        foreach ($defauts as $k => $v) {
            DB::table('store_settings')->updateOrInsert(['key' => $k], ['value' => $v, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('quote_id');
        });
        Schema::dropIfExists('store_quote_lines');
        Schema::dropIfExists('store_quotes');
        Schema::table('store_order_lines', function (Blueprint $table): void {
            $table->dropColumn('price_type_code');
        });
        DB::table('store_settings')->whereIn('key', ['quote_from', 'tiers_public', 'quote_validity_days', 'quote_intro'])->delete();
    }
};
