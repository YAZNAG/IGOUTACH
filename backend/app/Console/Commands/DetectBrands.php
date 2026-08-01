<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Détecte la marque de chaque article sans marque, par correspondance de mot
 * entier (insensible casse/accents) dans le nom. Rejouable, non destructive.
 */
final class DetectBrands extends Command
{
    protected $signature = 'catalog:detect-brands {--dry-run : Affiche sans écrire} {--apply : Applique les affectations sûres}';

    protected $description = 'Détecte et rattache les marques des articles depuis leur désignation';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        if (! $apply) {
            $this->info('Mode simulation (--dry-run). Utilisez --apply pour écrire.');
        }

        /** @var array<int, array{name: string, id: int, regex: string}> $brands */
        $brands = Brand::query()->where('is_active', true)->get(['id', 'name'])
            ->map(fn (Brand $b): array => [
                'id' => $b->id,
                'name' => $b->name,
                'regex' => '/\b'.preg_quote($this->normalize($b->name), '/').'\b/',
            ])->all();

        $rows = [];
        $applied = 0;

        Product::query()->whereNull('brand_id')->orderBy('name')
            ->chunkById(200, function ($products) use ($brands, $apply, &$rows, &$applied): void {
                foreach ($products as $product) {
                    $haystack = $this->normalize($product->name);
                    $matches = [];
                    foreach ($brands as $brand) {
                        if (preg_match($brand['regex'], $haystack) === 1) {
                            $matches[] = $brand;
                        }
                    }

                    $confidence = count($matches) === 1 ? 'sûre' : (count($matches) > 1 ? 'ambiguë' : 'aucune');
                    $detected = count($matches) === 1 ? $matches[0]['name'] : '—';

                    $rows[] = [$product->sku, Str::limit($product->name, 40), $detected, $confidence];

                    if ($apply && $confidence === 'sûre') {
                        $product->update(['brand_id' => $matches[0]['id']]);
                        $applied++;
                    }
                }
            });

        $this->table(['Référence', 'Article', 'Marque détectée', 'Confiance'], $rows);

        $sure = count(array_filter($rows, fn (array $r): bool => $r[3] === 'sûre'));
        $this->info(sprintf('%d article(s) sans marque · %d détection(s) sûre(s)%s.', count($rows), $sure, $apply ? " · {$applied} appliquée(s)" : ''));

        return self::SUCCESS;
    }

    private function normalize(string $value): string
    {
        return Str::upper(Str::ascii($value));
    }
}
