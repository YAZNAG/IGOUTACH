<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Contracts;

use App\Domain\Pricing\DTOs\PriceLevelData;
use App\Domain\Pricing\Models\ProductPrice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface ProductPriceRepositoryInterface
{
    /**
     * Prix en vigueur d'un article, indexés par code de niveau.
     *
     * @return Collection<string, ProductPrice>
     */
    public function currentFor(int $productId): Collection;

    /**
     * Prix en vigueur pour un niveau à une date donnée.
     */
    public function activePrice(int $productId, int $priceTypeId, ?CarbonInterface $at = null): ?ProductPrice;

    /**
     * Remplace les niveaux fournis (clôt la ligne courante, insère la nouvelle).
     *
     * @param  list<PriceLevelData>  $levels
     */
    public function replace(int $productId, array $levels, ?int $userId): void;

    /**
     * Historique complet d'un article, du plus récent au plus ancien.
     *
     * @return Collection<int, ProductPrice>
     */
    public function historyFor(int $productId): Collection;
}
