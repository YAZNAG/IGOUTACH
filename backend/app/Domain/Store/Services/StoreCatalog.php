<?php

declare(strict_types=1);

namespace App\Domain\Store\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Store\Models\StoreSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Le catalogue tel que la boutique le montre : articles actifs, visibles en
 * ligne, avec leurs paliers de prix, leur stock total et leurs photos.
 * Tout vient de la base de gestion ; rien n'est dupliqué.
 */
final class StoreCatalog
{
    /** Prix détail en vigueur, sinon le prix de vente de la fiche. */
    private const SQL_PRIX = "COALESCE((SELECT pp.amount FROM product_prices pp JOIN price_types pt ON pt.id = pp.price_type_id WHERE pp.product_id = products.id AND pt.code = 'detail' AND pp.valid_to IS NULL LIMIT 1), products.sale_price, 0)";

    /**
     * Le plus petit prix en vigueur, tous paliers confondus : c'est lui qui
     * décide si l'article est vendable. Un article tarifé en gros seulement
     * resterait invisible si l'on ne regardait que le détail.
     */
    private const SQL_PRIX_MIN = 'COALESCE((SELECT MIN(pp.amount) FROM product_prices pp JOIN price_types pt ON pt.id = pp.price_type_id WHERE pp.product_id = products.id AND pp.valid_to IS NULL AND pt.is_active = 1 AND pp.amount > 0), products.sale_price, 0)';

    private const SQL_STOCK = '(SELECT COALESCE(SUM(s.quantity), 0) FROM stocks s WHERE s.product_id = products.id)';

    /**
     * Paliers déjà chargés, par article. Évite une requête par carte quand la
     * grille en affiche soixante.
     *
     * @var Collection<array-key, Collection<int, \stdClass>>|null
     */
    private ?Collection $paliersCharges = null;

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
            // Vente en gros : à partir de quel palier on passe par un devis.
            'quote_from' => $this->paliersDevis(),
            'tiers_public' => ($r['tiers_public'] ?? '1') === '1',
            'quote_intro' => $r['quote_intro'] ?? '',
        ];
    }

    /**
     * Les codes de palier qui imposent un devis plutôt qu'une commande
     * directe. Choisir « demi-gros » entraîne « gros » : on ne vend pas le
     * palier du dessus en libre-service quand celui du dessous passe par un
     * devis.
     *
     * @return list<string>
     */
    public function paliersDevis(): array
    {
        return match (StoreSetting::valeur('quote_from', 'semi_gros')) {
            'gros' => [PriceType::GROS],
            'semi_gros' => [PriceType::SEMI_GROS, PriceType::GROS],
            default => [],
        };
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
            // Sans aucun prix en vigueur, un article ne se vend pas en ligne.
            ->whereRaw(self::SQL_PRIX_MIN.' > 0');
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
            ->whereRaw(self::SQL_PRIX_MIN.' > 0')
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

        $page = $q->paginate(max(12, min((int) ($filtres['per_page'] ?? 24), 60)));

        $this->precharger($page->getCollection());

        return $page;
    }

    /**
     * Charge en une seule requête les paliers d'un lot d'articles.
     *
     * @param  iterable<Product>  $articles
     */
    public function precharger(iterable $articles): void
    {
        $ids = [];
        foreach ($articles as $article) {
            $ids[] = $article->id;
        }
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            $this->paliersCharges = collect();

            return;
        }

        $this->paliersCharges = DB::table('product_prices as pp')
            ->join('price_types as pt', 'pt.id', '=', 'pp.price_type_id')
            ->whereIn('pp.product_id', $ids)
            ->whereNull('pp.valid_to')
            ->where('pt.is_active', true)
            ->where('pp.amount', '>', 0)
            ->orderBy('pt.rank')
            ->get([
                'pp.product_id',
                'pt.code',
                'pt.name',
                'pt.rank',
                // Le seuil de la ligne prime sur celui du type : c'est là que
                // vit le palier propre à un article.
                DB::raw('COALESCE(pp.min_quantity, pt.min_quantity) as seuil'),
                'pp.amount',
            ])
            ->groupBy('product_id');
    }

    /**
     * Les paliers d'un article, du plus petit seuil au plus grand.
     *
     * @return list<array{code: string, label: string, rank: int, min_quantity: int, price: float, on_quote: bool}>
     */
    public function paliersDe(Product $p): array
    {
        if ($this->paliersCharges === null || ! $this->paliersCharges->has($p->id)) {
            $this->precharger([$p]);
        }

        $surDevis = $this->paliersDevis();

        /** @var Collection<int, \stdClass> $lignes */
        $lignes = $this->paliersCharges?->get($p->id) ?? collect();

        return array_values($lignes
            ->sortBy([['seuil', 'asc'], ['rank', 'asc']])
            ->map(static fn (\stdClass $r): array => [
                'code' => (string) $r->code,
                'label' => (string) $r->name,
                // Le rang départage quand deux paliers ont le même seuil :
                // c'est lui qui fait foi côté gestion, et la vitrine doit
                // pouvoir trancher pareil.
                'rank' => (int) $r->rank,
                'min_quantity' => max(1, (int) $r->seuil),
                'price' => round((float) $r->amount, 2),
                'on_quote' => in_array((string) $r->code, $surDevis, true),
            ])
            ->all());
    }

    /**
     * La quantité à partir de laquelle la vente de cet article passe par un
     * devis. Null quand tout se commande en ligne.
     */
    public function seuilDevis(Product $p): ?int
    {
        $seuils = array_column(
            array_filter($this->paliersDe($p), static fn (array $t): bool => $t['on_quote']),
            'min_quantity',
        );

        return $seuils === [] ? null : min($seuils);
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
            'tiers' => $this->paliersDe($p),
            'quote_from_quantity' => $this->seuilDevis($p),
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

    /**
     * Prix affiché par défaut : le détail, c'est-à-dire le prix d'une unité.
     * À défaut de tarif détail, le premier palier disponible.
     */
    public function prixDe(Product $p): float
    {
        $prix = round((float) ($p->getAttribute('store_price') ?? 0), 2);

        if ($prix > 0) {
            return $prix;
        }

        $paliers = $this->paliersDe($p);

        return $paliers === [] ? 0.0 : $paliers[0]['price'];
    }

    public function stockDe(Product $p): int
    {
        return (int) ($p->getAttribute('stock_total') ?? 0);
    }
}
