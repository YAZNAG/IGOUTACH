<?php

declare(strict_types=1);

namespace App\Domain\Stock\Contracts;

interface StockReaderInterface
{
    public function quantityFor(int $warehouseId, int $productId): int;

    /**
     * Quantité consolidée d'un article, tous lieux confondus.
     */
    public function globalQuantityFor(int $productId): int;
}
