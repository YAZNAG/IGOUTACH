<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Product;
use App\Domain\Pricing\Actions\SetProductPricesAction;
use App\Domain\Pricing\DTOs\PriceLevelData;
use App\Domain\Pricing\Models\PriceType;
use Illuminate\Database\Seeder;

/**
 * Données de test : pose les 3 niveaux de prix pour chaque article.
 * détail = prix de vente s'il existe, sinon prix d'achat + 30 % de marge ;
 * demi-gros = détail -8 % ; gros = détail -15 %.
 */
final class DemoPricingSeeder extends Seeder
{
    public function run(): void
    {
        $action = app(SetProductPricesAction::class);

        Product::query()
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($action): void {
                foreach ($products as $product) {
                    $sale = (float) $product->sale_price;
                    $cost = (float) $product->cost_price;

                    $detail = $sale > 0 ? $sale : ($cost > 0 ? round($cost * 1.30, 2) : 0.0);
                    if ($detail <= 0) {
                        continue;
                    }

                    $semi = round($detail * 0.92, 2);
                    $gros = round($detail * 0.85, 2);

                    $action->execute($product->id, [
                        new PriceLevelData(PriceType::DETAIL, $detail),
                        new PriceLevelData(PriceType::SEMI_GROS, $semi, 0.0, 10),
                        new PriceLevelData(PriceType::GROS, $gros, 0.0, 50),
                    ]);
                }
            });
    }
}
