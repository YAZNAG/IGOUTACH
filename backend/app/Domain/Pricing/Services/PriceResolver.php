<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Domain\Customers\Models\Customer;
use App\Domain\Pricing\Contracts\PriceResolverInterface;
use App\Domain\Pricing\Contracts\ProductPriceRepositoryInterface;
use App\Domain\Pricing\DTOs\ResolvedPrice;
use App\Domain\Pricing\Exceptions\NoPriceDefinedException;
use App\Domain\Pricing\Models\PriceType;
use Carbon\CarbonInterface;

/**
 * Sélectionne le prix applicable : niveau du rang le plus élevé dont le seuil
 * de quantité est atteint, sinon le détail. (La prise en compte du
 * default_price_type_id client arrivera avec le module Clients.)
 */
final class PriceResolver implements PriceResolverInterface
{
    public function __construct(
        private readonly ProductPriceRepositoryInterface $prices,
    ) {}

    public function resolve(
        int $productId,
        int $quantity,
        ?int $customerId = null,
        ?CarbonInterface $at = null,
    ): ResolvedPrice {
        // Type de prix par défaut du client : prioritaire s'il est défini.
        if ($customerId !== null) {
            $customerTypeId = Customer::query()
                ->whereKey($customerId)
                ->value('price_type_id');

            if ($customerTypeId !== null) {
                $customerType = PriceType::query()->whereKey($customerTypeId)->where('is_active', true)->first();
                $price = $customerType !== null ? $this->prices->activePrice($productId, $customerType->id, $at) : null;

                if ($customerType !== null && $price !== null) {
                    return new ResolvedPrice(
                        amount: (float) $price->amount,
                        priceTypeId: $customerType->id,
                        priceTypeCode: $customerType->code,
                        reason: "Type de prix par défaut du client (« {$customerType->name} »).",
                    );
                }
            }
        }

        $types = PriceType::query()
            ->where('is_active', true)
            ->orderByDesc('rank')
            ->get();

        foreach ($types as $type) {
            $price = $this->prices->activePrice($productId, $type->id, $at);

            if ($price === null) {
                continue;
            }

            $threshold = $price->min_quantity ?? $type->min_quantity;

            if ($quantity >= $threshold) {
                return new ResolvedPrice(
                    amount: (float) $price->amount,
                    priceTypeId: $type->id,
                    priceTypeCode: $type->code,
                    reason: "Palier « {$type->name} » atteint ({$quantity} ≥ {$threshold}).",
                );
            }
        }

        // Repli : le détail en vigueur.
        $detail = PriceType::query()->where('code', PriceType::DETAIL)->first();
        $detailPrice = $detail !== null ? $this->prices->activePrice($productId, $detail->id, $at) : null;

        if ($detail === null || $detailPrice === null) {
            throw NoPriceDefinedException::for($productId);
        }

        return new ResolvedPrice(
            amount: (float) $detailPrice->amount,
            priceTypeId: $detail->id,
            priceTypeCode: $detail->code,
            reason: 'Prix de détail par défaut.',
        );
    }
}
