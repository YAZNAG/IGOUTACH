<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTOs;

/**
 * Données d'entrée pour créer ou mettre à jour un article.
 *
 * En paramétrage, seules les informations fixes sont saisies (réf, nom,
 * description, seuil min, catégorie). Les prix sont optionnels (défaut 0) et
 * gérés via l'écran « Tarifs de vente ». L'unité est optionnelle : le
 * repository applique l'unité par défaut si elle n'est pas fournie.
 */
final readonly class ProductData
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $categoryId,
        public ?int $unitId = null,
        public float $costPrice = 0.0,
        public float $salePrice = 0.0,
        public float $taxRate = 0.0,
        public ?string $barcode = null,
        public ?string $description = null,
        public ?int $brandId = null,
        public bool $isSerialized = false,
        public ?int $minStock = null,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sku: (string) $data['sku'],
            name: (string) $data['name'],
            categoryId: (int) $data['category_id'],
            unitId: isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            costPrice: (float) ($data['cost_price'] ?? 0.0),
            salePrice: (float) ($data['sale_price'] ?? 0.0),
            taxRate: (float) ($data['tax_rate'] ?? 0.0),
            barcode: isset($data['barcode']) ? (string) $data['barcode'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            isSerialized: (bool) ($data['is_serialized'] ?? false),
            minStock: isset($data['min_stock']) ? (int) $data['min_stock'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * Attributs pour la persistance. Les clés à null sont écartées afin de ne
     * pas écraser des valeurs existantes (ex. unité ou prix lors d'un update).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return array_filter([
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->categoryId,
            'unit_id' => $this->unitId,
            'cost_price' => $this->costPrice,
            'sale_price' => $this->salePrice,
            'tax_rate' => $this->taxRate,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'brand_id' => $this->brandId,
            'is_serialized' => $this->isSerialized,
            'min_stock' => $this->minStock,
            'is_active' => $this->isActive,
        ], static fn ($value) => $value !== null);
    }
}
