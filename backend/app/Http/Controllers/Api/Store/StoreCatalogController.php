<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Store;

use App\Domain\Catalog\Models\Product;
use App\Domain\Store\Services\StoreCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogue public de la boutique : réglages, catégories, articles.
 * Lecture seule, sans authentification.
 */
final class StoreCatalogController extends Controller
{
    public function __construct(private readonly StoreCatalog $catalogue) {}

    public function settings(): JsonResponse
    {
        return response()->json(['data' => $this->catalogue->reglages()]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => $this->catalogue->categories()]);
    }

    public function products(Request $request): JsonResponse
    {
        $page = $this->catalogue->produits([
            'q' => $request->string('q')->value(),
            'category_id' => $request->integer('category_id'),
            'featured' => $request->boolean('featured'),
            'in_stock' => $request->boolean('in_stock'),
            'sort' => $request->string('sort')->value(),
            'per_page' => $request->integer('per_page', 24),
        ]);

        return response()->json([
            'data' => array_map(fn (Product $p): array => $this->catalogue->carte($p), $page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function product(int $id): JsonResponse
    {
        $p = $this->catalogue->vendables()->whereKey($id)->first();

        if ($p === null) {
            return response()->json(['message' => 'Article indisponible.'], 404);
        }

        // Quelques articles de la même catégorie, pour continuer la visite.
        $similaires = $this->catalogue->vendables()
            ->where('products.category_id', $p->category_id)
            ->where('products.id', '!=', $p->id)
            ->inRandomOrder()->limit(8)->get();

        return response()->json(['data' => [
            ...$this->catalogue->fiche($p),
            'related' => $similaires->map(fn (Product $s): array => $this->catalogue->carte($s))->values()->all(),
        ]]);
    }
}
