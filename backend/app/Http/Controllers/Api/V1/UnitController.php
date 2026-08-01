<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\DeleteUnitAction;
use App\Domain\Catalog\Actions\SaveUnitAction;
use App\Domain\Catalog\Exceptions\UnitInUseException;
use App\Domain\Catalog\Models\Unit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UnitController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $units = Unit::query()
            ->withCount('products')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return UnitResource::collection($units);
    }

    public function store(StoreUnitRequest $request, SaveUnitAction $action): JsonResponse
    {
        /** @var array{code: string, name: string, is_decimal?: bool, position?: int, is_active?: bool} $data */
        $data = $request->validated();
        $data['is_decimal'] ??= false;

        $unit = $action->create($data);

        return UnitResource::make($unit)->response()->setStatusCode(201);
    }

    public function update(UpdateUnitRequest $request, Unit $unit, SaveUnitAction $action): JsonResponse|UnitResource
    {
        /** @var array{code: string, name: string, is_decimal?: bool, position?: int, is_active?: bool} $data */
        $data = $request->validated();
        $data['is_decimal'] ??= false;

        try {
            $updated = $action->update($unit, $data);
        } catch (UnitInUseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return UnitResource::make($updated);
    }

    public function destroy(Unit $unit, DeleteUnitAction $action): JsonResponse
    {
        try {
            $action->execute($unit);
        } catch (UnitInUseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Unité désactivée.']);
    }
}
