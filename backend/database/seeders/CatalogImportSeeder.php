<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Import du référentiel catalogue (catégories + articles) issu de igx.xlsx.
 * Idempotent : catégories par nom, articles par SKU (updateOrCreate).
 */
final class CatalogImportSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/catalog_import.json');

        if (! is_file($path)) {
            $this->command->warn("Fichier d'import introuvable : {$path} — import ignoré.");

            return;
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException("Lecture impossible : {$path}");
        }

        /** @var array{categories: list<string>, articles: list<array<string, mixed>>} $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $unitId = (int) (Unit::query()->where('code', 'pcs')->value('id')
            ?? Unit::query()->value('id')
            ?? Unit::create(['code' => 'pcs', 'name' => 'Pièce'])->id);

        // Catégories
        $categoryIds = [];
        foreach ($data['categories'] as $name) {
            $category = Category::updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
            $categoryIds[mb_strtoupper($name)] = $category->id;
        }

        // Catégorie de repli pour les articles sans catégorie.
        $fallbackId = Category::updateOrCreate(['name' => 'DIVERS'], ['is_active' => true])->id;

        // Niveau de prix « détail » pour amorcer les tarifs 3 niveaux.
        $detailTypeId = PriceType::query()->where('code', PriceType::DETAIL)->value('id');

        // Articles
        foreach ($data['articles'] as $row) {
            $categoryKey = isset($row['category']) ? mb_strtoupper((string) $row['category']) : null;

            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'category_id' => ($categoryKey !== null ? ($categoryIds[$categoryKey] ?? null) : null) ?? $fallbackId,
                    'unit_id' => $unitId,
                    'cost_price' => $row['cost_price'] ?? 0,
                    'sale_price' => $row['sale_price'] ?? 0,
                    'tax_rate' => $row['tax_rate'] ?? 0,
                    'min_stock' => $row['min_stock'] ?? null,
                    'is_active' => true,
                ],
            );

            // Prix détail en vigueur (append-only) — créé une seule fois.
            if ($detailTypeId !== null) {
                $hasDetail = ProductPrice::query()
                    ->where('product_id', $product->id)
                    ->where('price_type_id', $detailTypeId)
                    ->whereNull('valid_to')
                    ->exists();

                if (! $hasDetail) {
                    ProductPrice::create([
                        'product_id' => $product->id,
                        'price_type_id' => $detailTypeId,
                        'amount' => $row['sale_price'] ?? 0,
                        'min_margin_percent' => 0,
                        'valid_from' => now(),
                    ]);
                }
            }
        }

        $this->command->info(sprintf(
            'Catalogue importé : %d catégories, %d articles.',
            count($data['categories']),
            count($data['articles']),
        ));
    }
}
