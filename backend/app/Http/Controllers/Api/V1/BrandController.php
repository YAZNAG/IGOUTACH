<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\DeleteBrandAction;
use App\Domain\Catalog\Exceptions\BrandInUseException;
use App\Domain\Catalog\Models\Brand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

final class BrandController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $brands = Brand::query()
            ->withCount('products')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return BrandResource::collection($brands);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = Brand::query()->create($request->validated());

        return BrandResource::make($brand)->response()->setStatusCode(201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        $brand->update($request->validated());

        return BrandResource::make($brand->refresh());
    }

    public function destroy(Brand $brand, DeleteBrandAction $action): JsonResponse
    {
        try {
            $action->execute($brand);
        } catch (BrandInUseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Marque désactivée.']);
    }

    public function logo(Request $request, Brand $brand): BrandResource
    {
        $request->validate([
            // Logo facultatif : PNG ou SVG, 500 Ko max.
            'logo' => ['required', 'file', 'mimes:png,svg', 'max:500'],
        ]);

        if ($brand->logo_path !== null) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $path = $request->file('logo')->store('brands', 'public');
        $brand->update(['logo_path' => $path]);

        return BrandResource::make($brand->refresh());
    }
}
