<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\AttributeTemplate;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductAttribute;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Fiche technique d'un article : liste des attributs + noms suggérés par la
 * catégorie (modèle), enregistrement en remplacement complet.
 */
final class ProductAttributeController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $attributes = $product->attributes()->get()->map(fn (ProductAttribute $a): array => [
            'name' => $a->name,
            'value' => $a->value,
        ])->values();

        $templateNames = AttributeTemplate::query()
            ->where('category_id', $product->category_id)
            ->orderBy('position')
            ->pluck('name')
            ->values();

        return response()->json(['data' => [
            'attributes' => $attributes,
            'template' => $templateNames,
        ]]);
    }

    public function save(Request $request, Product $product): JsonResponse
    {
        /** @var array{attributes: list<array{name: string, value: string}>} $data */
        $data = $request->validate([
            'attributes' => ['present', 'array', 'max:50'],
            'attributes.*.name' => ['required', 'string', 'max:120'],
            'attributes.*.value' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($product, $data): void {
            $product->attributes()->delete();

            foreach ($data['attributes'] as $position => $attribute) {
                $product->attributes()->create([
                    'name' => $attribute['name'],
                    'value' => $attribute['value'],
                    'position' => $position,
                ]);
            }
        });

        return $this->index($product);
    }

    /**
     * Modèle d'attributs de la catégorie (remplacement complet de la liste).
     */
    public function saveTemplate(Request $request, int $categoryId): JsonResponse
    {
        abort_unless(Category::query()->whereKey($categoryId)->exists(), 404);

        /** @var array{names: list<string>} $data */
        $data = $request->validate([
            'names' => ['present', 'array', 'max:50'],
            'names.*' => ['required', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($categoryId, $data): void {
            AttributeTemplate::query()->where('category_id', $categoryId)->delete();

            foreach (array_values(array_unique($data['names'])) as $position => $name) {
                AttributeTemplate::query()->create([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'position' => $position,
                ]);
            }
        });

        return response()->json(['data' => AttributeTemplate::query()
            ->where('category_id', $categoryId)->orderBy('position')->pluck('name')->values()]);
    }
}
