<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Repositories;

use App\Domain\Pricing\Contracts\ProductPriceRepositoryInterface;
use App\Domain\Pricing\DTOs\PriceLevelData;
use App\Domain\Pricing\Models\PriceType;
use App\Domain\Pricing\Models\ProductPrice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProductPriceRepository implements ProductPriceRepositoryInterface
{
    /**
     * @return Collection<string, ProductPrice>
     */
    public function currentFor(int $productId): Collection
    {
        $codes = PriceType::query()->pluck('code', 'id');

        return ProductPrice::query()
            ->where('product_id', $productId)
            ->whereNull('valid_to')
            ->get()
            ->keyBy(fn (ProductPrice $price): string => (string) $codes[$price->price_type_id]);
    }

    public function activePrice(int $productId, int $priceTypeId, ?CarbonInterface $at = null): ?ProductPrice
    {
        $at ??= now();

        return ProductPrice::query()
            ->where('product_id', $productId)
            ->where('price_type_id', $priceTypeId)
            ->where('valid_from', '<=', $at)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', $at))
            ->orderByDesc('valid_from')
            ->first();
    }

    /**
     * @param  list<PriceLevelData>  $levels
     */
    public function replace(int $productId, array $levels, ?int $userId): void
    {
        $typeIds = PriceType::query()->pluck('id', 'code');

        DB::transaction(function () use ($productId, $levels, $userId, $typeIds): void {
            $now = now();

            foreach ($levels as $level) {
                $priceTypeId = (int) $typeIds[$level->priceTypeCode];

                // Clôt la ligne en vigueur (append-only : jamais d'UPDATE sur amount).
                ProductPrice::query()
                    ->where('product_id', $productId)
                    ->where('price_type_id', $priceTypeId)
                    ->whereNull('valid_to')
                    ->update(['valid_to' => $now]);

                ProductPrice::create([
                    'product_id' => $productId,
                    'price_type_id' => $priceTypeId,
                    'amount' => $level->amount,
                    'min_quantity' => $level->minQuantity,
                    'min_margin_percent' => $level->minMarginPercent,
                    'valid_from' => $now,
                    'created_by' => $userId,
                ]);
            }
        });
    }

    /**
     * @return Collection<int, ProductPrice>
     */
    public function historyFor(int $productId): Collection
    {
        return ProductPrice::query()
            ->with('priceType:id,code,name')
            ->where('product_id', $productId)
            ->orderByDesc('valid_from')
            ->get();
    }
}
