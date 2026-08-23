<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixe les paliers de quantité : détail 1, demi-gros 3, gros 10.
 *
 * Les seuils vivaient à deux endroits. Le type de tarif porte le seuil de
 * référence, mais chaque ligne de prix pouvait le surcharger — et 7 746
 * lignes le faisaient, en recopiant simplement l'ancien seuil du type.
 * Changer le type seul n'aurait donc rien changé pour les deux tiers du
 * catalogue.
 *
 * On remet donc le seuil là où il a un sens — sur le type — et on efface les
 * surcharges des lignes en vigueur. Les versions historiques gardent la leur :
 * elles décrivent ce qui s'appliquait à l'époque, et les réécrire ferait
 * mentir l'historique des prix.
 */
return new class extends Migration
{
    /** Le palier de chaque type, en quantité. */
    private const PALIERS = [
        'detail' => 1,
        'semi_gros' => 3,
        'gros' => 10,
    ];

    public function up(): void
    {
        foreach (self::PALIERS as $code => $quantite) {
            DB::table('price_types')->where('code', $code)->update(['min_quantity' => $quantite]);
        }

        // Seules les lignes en vigueur : l'historique n'est pas reecrit.
        DB::table('product_prices')
            ->whereNull('valid_to')
            ->whereNotNull('min_quantity')
            ->update(['min_quantity' => null]);
    }

    public function down(): void
    {
        // Les anciens seuils, tels qu'ils etaient avant cette migration.
        foreach (['detail' => 1, 'semi_gros' => 10, 'gros' => 50] as $code => $quantite) {
            DB::table('price_types')->where('code', $code)->update(['min_quantity' => $quantite]);
        }

        // Les surcharges effacees ne sont pas restaurees : elles recopiaient
        // le seuil du type, que l'on vient de remettre.
    }
};
