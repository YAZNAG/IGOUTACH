<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\TaxRate;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveTaxRateRequest;
use App\Http\Resources\TaxRateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class TaxRateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TaxRateResource::collection(
            TaxRate::query()->orderBy('position')->orderBy('rate')->get(),
        );
    }

    public function store(SaveTaxRateRequest $request): JsonResponse
    {
        /** @var array{rate: float, label: string, is_default?: bool, position?: int, is_active?: bool} $data */
        $data = $request->validated();

        $taxRate = DB::transaction(function () use ($data): TaxRate {
            $rate = TaxRate::query()->create($data);
            $this->ensureSingleDefault($rate);

            return $rate;
        });

        return TaxRateResource::make($taxRate)->response()->setStatusCode(201);
    }

    public function update(SaveTaxRateRequest $request, TaxRate $taxRate): TaxRateResource
    {
        /** @var array{rate: float, label: string, is_default?: bool, position?: int, is_active?: bool} $data */
        $data = $request->validated();

        DB::transaction(function () use ($taxRate, $data): void {
            $taxRate->update($data);
            $this->ensureSingleDefault($taxRate);
        });

        return TaxRateResource::make($taxRate->refresh());
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->delete();

        return response()->json(['message' => 'Taux supprimé.']);
    }

    /**
     * Un seul taux par défaut à la fois.
     */
    private function ensureSingleDefault(TaxRate $current): void
    {
        if ($current->is_default) {
            TaxRate::query()->whereKeyNot($current->getKey())->where('is_default', true)->update(['is_default' => false]);
        }
    }
}
