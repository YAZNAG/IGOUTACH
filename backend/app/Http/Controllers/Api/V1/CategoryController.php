<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\CreateCategoryAction;
use App\Domain\Catalog\Actions\DeleteCategoryAction;
use App\Domain\Catalog\Actions\ReorderCategoriesAction;
use App\Domain\Catalog\Actions\UpdateCategoryAction;
use App\Domain\Catalog\DTOs\CategoryData;
use App\Domain\Catalog\Exceptions\CategoryInUseException;
use App\Domain\Catalog\Exceptions\CategoryReorderException;
use App\Domain\Catalog\Models\Category;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderCategoriesRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Support\Export\HtmlTable;
use App\Support\Query\Sortable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('name', 'like', "%{$request->string('search')->value()}%"));

        Sortable::apply($categories, $request, [
            'name' => 'name',
            'products_count' => 'products_count',
        ], 'name');

        return CategoryResource::collection($categories->paginate(50));
    }

    public function reorder(ReorderCategoriesRequest $request, ReorderCategoriesAction $action): JsonResponse
    {
        /** @var array{items: list<array{id: int, position: int, parent_id: int|null}>} $data */
        $data = $request->validated();

        try {
            $action->execute($data['items']);
        } catch (CategoryReorderException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Arborescence mise à jour.']);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $category = $action->execute(CategoryData::fromArray($request->validated()));

        return CategoryResource::make($category)->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): CategoryResource
    {
        return CategoryResource::make(
            $action->execute($category, CategoryData::fromArray($request->validated())),
        );
    }

    public function destroy(Category $category, DeleteCategoryAction $action): JsonResponse|Response
    {
        try {
            $action->execute($category);
        } catch (CategoryInUseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }

    public function bulkDestroy(Request $request, DeleteCategoryAction $action): JsonResponse
    {
        /** @var array{ids: list<int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;
        $blocked = [];

        foreach (Category::query()->whereIn('id', $validated['ids'])->get() as $category) {
            try {
                $action->execute($category);
                $deleted++;
            } catch (CategoryInUseException $e) {
                $blocked[] = ['id' => $category->id, 'name' => $category->name, 'reason' => $e->getMessage()];
            }
        }

        return response()->json(['data' => ['deleted' => $deleted, 'blocked' => $blocked]]);
    }

    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $headings = ['Nom', 'Nb articles', 'Statut'];

        $rows = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c): array => [
                $c->name,
                $c->products_count,
                $c->is_active ? 'Actif' : 'Inactif',
            ])
            ->values()
            ->all();

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render('Catégories', $headings, $rows))
                ->download('categories.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'categories.xlsx');
    }
}
