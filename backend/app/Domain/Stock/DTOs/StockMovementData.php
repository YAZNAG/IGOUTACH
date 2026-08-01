<?php

declare(strict_types=1);

namespace App\Domain\Stock\DTOs;

/**
 * Données d'un mouvement de stock à appliquer.
 */
final readonly class StockMovementData
{
    public function __construct(
        public int $warehouseId,
        public int $productId,
        public int $quantity,          // toujours positif ; le sens est porté par le type de mouvement
        public string $movementTypeCode,
        public float $unitCost = 0.0,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?int $userId = null,
        public ?string $note = null,
        public ?string $occurredAt = null,   // date du mouvement (sinon = maintenant)
    ) {}
}
