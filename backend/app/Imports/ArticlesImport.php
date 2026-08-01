<?php

declare(strict_types=1);

namespace App\Imports;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import d'articles depuis un fichier Excel/CSV.
 * En-têtes attendues (accents/espaces tolérés) : Nom, Référence, Prix d'achat,
 * Prix de vente, Alerte Stock Minimal, TVA, Catégorie, Description.
 */
final class ArticlesImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    private int $unitId;

    private int $fallbackCategoryId;

    private ?int $detailTypeId;

    /**
     * @var array<string, int>
     */
    private array $categoryCache = [];

    public function __construct()
    {
        $this->unitId = (int) (Unit::query()->where('code', 'PCE')->value('id')
            ?? Unit::query()->value('id')
            ?? Unit::create(['code' => 'PCE', 'name' => 'Pièce'])->id);

        $this->fallbackCategoryId = (int) Category::updateOrCreate(['name' => 'DIVERS'], ['is_active' => true])->id;
        $this->detailTypeId = PriceType::query()->where('code', PriceType::DETAIL)->value('id');
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = $this->str($row, ['nom', 'name']);
            $ref = $this->str($row, ['reference', 'référence', 'ref', 'sku']);
            $sku = $ref !== '' ? $ref : $name;

            if ($sku === '') {
                $this->skipped++;

                continue;
            }

            $categoryId = $this->resolveCategory($this->str($row, ['categorie', 'catégorie', 'category']));
            $salePrice = $this->num($row, ['prix_de_vente', 'prix_vente', 'sale_price']);

            $existing = Product::withTrashed()->where('sku', $sku)->first();

            $product = Product::withTrashed()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name !== '' ? $name : $sku,
                    'description' => $this->str($row, ['description']) ?: null,
                    'category_id' => $categoryId,
                    'unit_id' => $this->unitId,
                    'cost_price' => $this->num($row, ['prix_d_achat', 'prix_dachat', 'cost_price']),
                    'sale_price' => $salePrice,
                    'tax_rate' => $this->num($row, ['tva', 'tax_rate']),
                    'min_stock' => (int) $this->num($row, ['alerte_stock_minimal', 'seuil_min', 'min_stock']),
                    'is_active' => true,
                ],
            );

            // Réactive un article précédemment supprimé qu'on ré-importe.
            if ($product->trashed()) {
                $product->restore();
            }

            $existing !== null ? $this->updated++ : $this->created++;

            $this->ensureDetailPrice($product->id, $salePrice);
        }
    }

    private function resolveCategory(string $name): int
    {
        if ($name === '') {
            return $this->fallbackCategoryId;
        }

        $key = mb_strtoupper($name);
        if (! isset($this->categoryCache[$key])) {
            $this->categoryCache[$key] = (int) Category::updateOrCreate(['name' => $name], ['is_active' => true])->id;
        }

        return $this->categoryCache[$key];
    }

    private function ensureDetailPrice(int $productId, float $amount): void
    {
        if ($this->detailTypeId === null) {
            return;
        }

        $exists = ProductPrice::query()
            ->where('product_id', $productId)
            ->where('price_type_id', $this->detailTypeId)
            ->whereNull('valid_to')
            ->exists();

        if (! $exists) {
            ProductPrice::create([
                'product_id' => $productId,
                'price_type_id' => $this->detailTypeId,
                'amount' => $amount,
                'valid_from' => now(),
            ]);
        }
    }

    /**
     * @param  Collection<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function str(Collection $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $row->get($key);
            if ($value !== null && $value !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * @param  Collection<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function num(Collection $row, array $keys): float
    {
        foreach ($keys as $key) {
            $value = $row->get($key);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }
}
