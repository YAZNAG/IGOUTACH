<?php

declare(strict_types=1);

namespace App\Domain\Store\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Contracts\PriceResolverInterface;
use App\Domain\Pricing\Exceptions\NoPriceDefinedException;

/**
 * Le prix que la boutique applique à une quantité donnée.
 *
 * Le moteur de tarification de la gestion fait déjà le travail : il retient
 * le palier du rang le plus élevé dont le seuil est atteint, et ce seuil peut
 * être propre à l'article. On s'appuie dessus plutôt que de le réécrire — un
 * prix calculé deux fois finit par diverger.
 */
final class StorePricing
{
    public function __construct(
        private readonly PriceResolverInterface $resolveur,
        private readonly StoreCatalog $catalogue,
    ) {}

    /**
     * Prix unitaire et palier appliqué pour cette quantité.
     *
     * @return array{price: float, code: string|null}
     */
    public function pour(Product $article, int $quantite): array
    {
        try {
            $resolu = $this->resolveur->resolve($article->id, max(1, $quantite));

            return ['price' => round($resolu->amount, 2), 'code' => $resolu->priceTypeCode];
        } catch (NoPriceDefinedException) {
            // Aucun tarif en vigueur : on retombe sur le prix de la fiche,
            // qui est ce que la vitrine affiche déjà.
            return ['price' => $this->catalogue->prixDe($article), 'code' => null];
        }
    }

    /**
     * Le palier atteint impose-t-il de passer par un devis ?
     */
    public function surDevis(?string $codePalier): bool
    {
        return $codePalier !== null && in_array($codePalier, $this->catalogue->paliersDevis(), true);
    }
}
