<?php

declare(strict_types=1);

namespace App\Domain\Store\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Store\Models\StoreSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Le catalogue tel que la boutique le montre : articles actifs, visibles en
 * ligne, avec un prix de vente détail, leur stock total et leurs photos.
 * Tout vient de la base de gestion ; rien n'est dupliqué.
 */
final class StoreCatalog
{
    /** Prix détail en vigueur, sinon le prix de vente de la fiche. */
    private const SQL_PRIX = "COALESCE((SELECT pp.amount FROM product_prices pp JOIN price_types pt ON pt.id = pp.price_type_id WHERE pp.product_id = products.id AND pt.code = 'detail' AND pp.valid_to IS NULL LIMIT 1), products.sale_price, 0)";

    private const SQL_STOCK = '(SELECT COALESCE(SUM(s.quantity), 0) FROM stocks s WHERE s.product_id = products.id)';

    /**
     * Réglages publics de la boutique.
     *
     * @return array<string, mixed>
     */
    public function reglages(): array
    {
        $r = StoreSetting::tous();

        return [
            'shop_name' => $r['shop_name'] ?? 'iGouTech Store',
            'phone' => $r['phone'] ?? '',
            'whatsapp' => $r['whatsapp'] ?? '',
            'email' => $r['email'] ?? '',
            'address' => $r['address'] ?? '',
            'shipping_fee' => (float) ($r['shipping_fee'] ?? 0),
            'free_shipping_from' => (float) ($r['free_shipping_from'] ?? 0),
            'payment_methods' => array_values(array_filter([
                ($r['payment_cod'] ?? '1') === '1' ? 'cod' : null,
                ($r['payment_transfer'] ?? '0') === '1' ? 'transfer' : null,
            ])),
            'bank_details' => $r['bank_details'] ?? '',
            'announcement' => $r['announcement'] ?? '',
            'hero_title' => $r['hero_title'] ?? '',
            'hero_subtitle' => $r['hero_subtitle'] ?? '',
        ];
    }

    /**
     * Base des articles vendables en ligne, avec prix détail, stock et photos.
     *
     * @return Builder<Product>
     */
    public function vendables(): Builder
    {
        return Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,code', 'images'])
            ->where('products.is_active', true)
            ->where('products.store_visible', true)
            ->select('products.*')
            ->selectRaw(self::SQL_PRIX.' as store_price')
            ->selectRaw(self::SQL_STOCK.' as stock_total')
            // Sans prix de vente, un article ne se vend pas en ligne.
            ->whereRaw(self::SQL_PRIX.' > 0');
    }

    /**
     * Catégories ayant au moins un article en ligne, avec leur nombre.
     *
     * @return list<array{id: int, name: string, count: int}>
     */
    public function categories(): array
    {
        $comptes = Product::query()
            ->where('products.is_active', true)
            ->where('products.store_visible', true)
            ->whereRaw(self::SQL_PRIX.' > 0')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->selectRaw('category_id, COUNT(*) as n')
            ->pluck('n', 'category_id');

        return DB::table('categories')
            ->whereIn('id', $comptes->keys()->all())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c): array => ['id' => (int) $c->id, 'name' => (string) $c->name, 'count' => (int) ($comptes[$c->id] ?? 0)])
            ->values()
            ->all();
    }

    /**
     * @param  array{q?: string, category_id?: int, featured?: bool, sort?: string, per_page?: int, in_stock?: bool}  $filtres
     * @return LengthAwarePaginator<int, Product>
     */
    public function produits(array $filtres): LengthAwarePaginator
    {
        $q = $this->vendables()
            ->when(($filtres['q'] ?? '') !== '', function (Builder $b) use ($filtres): void {
                foreach (preg_split('/\s+/u', trim((string) $filtres['q'])) ?: [] as $mot) {
                    $b->where(fn (Builder $w) => $w->where('products.name', 'like', "%{$mot}%")->orWhere('products.sku', 'like', "%{$mot}%"));
                }
            })
            ->when(($filtres['category_id'] ?? 0) > 0, fn (Builder $b) => $b->where('products.category_id', (int) $filtres['category_id']))
            ->when(($filtres['featured'] ?? false) === true, fn (Builder $b) => $b->where('products.store_featured', true))
            ->when(($filtres['in_stock'] ?? false) === true, fn (Builder $b) => $b->whereRaw(self::SQL_STOCK.' > 0'));

        match ($filtres['sort'] ?? 'name') {
            'price_asc' => $q->orderByRaw(self::SQL_PRIX.' asc'),
            'price_desc' => $q->orderByRaw(self::SQL_PRIX.' desc'),
            'newest' => $q->orderByDesc('products.id'),
            default => $q->orderBy('products.name'),
        };

        return $q->paginate(max(12, min((int) ($filtres['per_page'] ?? 24), 60)));
    }

    /**
     * @return array<string, mixed>
     */
    public function carte(Product $p): array
    {
        $images = $p->images->sortBy([['is_main', 'desc'], ['position', 'asc']])->values();
        $stock = (int) $p->getAttribute('stock_total');

        return [
            'id' => $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
            'category' => $p->category?->name,
            'category_id' => $p->category_id,
            'brand' => $p->brand?->name,
            'price' => $this->prixDe($p),
            'in_stock' => $stock > 0,
            // Le niveau exact n'est pas public : en stock, limité, ou rupture.
            'stock_level' => $stock <= 0 ? 'out' : ($stock <= 3 ? 'low' : 'in'),
            'featured' => (bool) $p->store_featured,
            'image' => $images->first() ? Storage::disk('public')->url($images->first()->path) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fiche(Product $p): array
    {
        $images = $p->images->sortBy([['is_main', 'desc'], ['position', 'asc']])->values();

        return [
            ...$this->carte($p),
            'description' => $p->store_description ?: $p->description,
            'images' => $images->map(fn ($i) => Storage::disk('public')->url($i->path))->values()->all(),
            'unit' => $p->unit?->code,
        ];
    }

    public function prixDe(Product $p): float
    {
        return round((float) ($p->getAttribute('store_price') ?? 0), 2);
    }

    public function stockDe(Product $p): int
    {
        return (int) ($p->getAttribute('stock_total') ?? 0);
    }
}
