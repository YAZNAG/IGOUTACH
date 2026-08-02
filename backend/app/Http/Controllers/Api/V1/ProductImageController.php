<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Médias d'un article : upload, image principale, suppression.
 * Les images sont facultatives et stockées sur le disque public.
 */
final class ProductImageController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json(['data' => $this->list($product)]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('image');
        $path = $file->store('products', 'public');

        $isFirst = $product->images()->count() === 0;

        $product->images()->create([
            'path' => (string) $path,
            'is_main' => $isFirst,
            'position' => $product->images()->count(),
        ]);

        return response()->json(['data' => $this->list($product)], 201);
    }

    public function setMain(Product $product, ProductImage $image): JsonResponse
    {
        abort_if($image->product_id !== $product->id, 404);

        $product->images()->update(['is_main' => false]);
        $image->update(['is_main' => true]);

        return response()->json(['data' => $this->list($product)]);
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_if($image->product_id !== $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $wasMain = $image->is_main;
        $image->delete();

        // Si la principale est supprimée, promeut la première restante.
        if ($wasMain) {
            $product->images()->orderBy('position')->first()?->update(['is_main' => true]);
        }

        return response()->json(['data' => $this->list($product)]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function list(Product $product): array
    {
        return $product->images()->get()->map(fn (ProductImage $i): array => [
            'id' => $i->id,
            'url' => Storage::disk('public')->url($i->path),
            'is_main' => $i->is_main,
        ])->values()->all();
    }
}
