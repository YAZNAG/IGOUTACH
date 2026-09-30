<?php

declare(strict_types=1);

namespace App\Support\Stock;

use Illuminate\Support\Carbon;

/**
 * Horodate un mouvement de stock saisi depuis un formulaire à date seule.
 *
 * Les réceptions fournisseur et les retours ne demandent qu'une date. Elle
 * arrive donc à minuit, et le journal — trié par date et heure — range ces
 * mouvements avant tout ce qui s'est passé dans la journée. Un retour
 * enregistré à 20 h 30 apparaissait ainsi *au-dessous* de la vente de 20 h 23
 * qu'il annulait, en donnant l'impression que le stock avait baissé après le
 * retour.
 *
 * Sur les 29 réceptions et les 3 retours enregistrés à ce jour, la date
 * choisie était toujours celle de la saisie : l'heure réelle est donc connue
 * dans tous les cas rencontrés.
 */
final class MovementInstant
{
    /**
     * @param  string|null  $date  Date choisie dans le formulaire, ou null.
     * @return string|null Horodatage à inscrire, ou null pour laisser le
     *                     dépôt poser l'heure courante.
     */
    public static function resolve(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $choisi = Carbon::parse($date);

        // Date du jour : l'heure de saisie est l'heure de l'opération, et
        // c'est elle qui met le journal dans l'ordre.
        if ($choisi->isToday()) {
            return Carbon::now()->format('Y-m-d H:i:s');
        }

        // Document antidaté : l'heure exacte de ce jour-là est inconnue.
        // L'inventer placerait le mouvement à un moment où rien ne s'est
        // produit — moins honnête que de le laisser en tête de journée.
        return $choisi->format('Y-m-d H:i:s');
    }
}
