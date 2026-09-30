<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use Barryvdh\DomPDF\PDF;

/**
 * Enveloppe DomPDF qui met en forme l'arabe de tout document produit.
 *
 * Branchée à la place de l'enveloppe du paquet : factures, bons, journaux et
 * exports de tableaux passent tous par loadHTML — loadView y compris —, si
 * bien qu'aucun contrôleur n'a à s'en soucier.
 */
class ArabicAwarePdf extends PDF
{
    public function loadHTML(string $string, ?string $encoding = null): self
    {
        return parent::loadHTML(ArabicShaper::html($string), $encoding);
    }
}
