<?php

declare(strict_types=1);

namespace App\Domain\Stock\Services;

use App\Domain\Stock\Contracts\StockValuationInterface;

/**
 * Valorisation au coût d'achat : le stock vaut le prix du dernier achat.
 *
 * L'application ne connaît qu'un seul coût. La moyenne pondérée des arrivages
 * — le CMUP — donnait un second chiffre pour la même marchandise : un article
 * acheté 180 pouvait se valoriser à 126 parce qu'un vieil arrivage tirait la
 * moyenne vers le bas, et le prix plancher d'une vente s'en trouvait faussé.
 *
 * L'entrée impose donc son prix au stock existant. C'est une revalorisation
 * assumée : à chaque achat, ce qui reste en rayon vaut ce qu'il coûterait à
 * racheter aujourd'hui, et « stocks.average_cost » ne peut plus s'écarter du
 * prix d'achat porté par la fiche article.
 */
final class PurchaseCostValuation implements StockValuationInterface
{
    public function newUnitCost(
        int $currentQty,
        float $currentCost,
        int $incomingQty,
        float $incomingCost,
    ): float {
        if ($incomingCost > 0) {
            return round($incomingCost, 2);
        }

        // Une entrée sans prix — un excédent d'inventaire, par exemple — ne
        // doit pas effacer ce que l'on sait déjà : le coût en place est
        // conservé. Zéro ne subsiste que si l'on ignore tout du prix.
        return round($currentCost, 2);
    }
}
