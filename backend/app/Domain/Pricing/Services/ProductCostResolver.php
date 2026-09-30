<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\Stock;
use Illuminate\Support\Facades\DB;

/**
 * Coûts de référence d'un article. Deux questions distinctes, deux réponses.
 *
 * - « Combien vaut ce que je détiens ? » → le CMUP, moyenne pondérée du stock
 *   encore en rayon. C'est le chiffre qui valorise un inventaire.
 * - « Combien me coûte cet article ? » → le prix du dernier achat. C'est le
 *   chiffre sur lequel on fixe un tarif et on juge une remise.
 *
 * Les confondre trompait : de la marchandise entrée par un inventaire
 * d'ouverture sans prix tirait la moyenne vers le bas, et un article acheté
 * 180 s'affichait à 126.
 */
final class ProductCostResolver
{
    /**
     * Coût d'achat : le prix du dernier bon de réception.
     *
     * À défaut de réception — la majorité des articles, entrés par
     * l'inventaire d'ouverture — c'est le prix porté par la fiche. Le CMUP ne
     * sert qu'en dernier recours, quand ni l'un ni l'autre n'existe : mieux
     * vaut une moyenne qu'un zéro.
     */
    public function purchaseCost(Product $product): float
    {
        $dernier = DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as r', 'r.id', '=', 'l.goods_receipt_id')
            ->where('l.product_id', $product->id)
            ->orderByDesc('r.received_at')
            ->orderByDesc('l.id')
            ->value('l.unit_price');

        if ($dernier !== null && (float) $dernier > 0) {
            return round((float) $dernier, 2);
        }

        if ((float) $product->cost_price > 0) {
            return round((float) $product->cost_price, 2);
        }

        return $this->unitCost($product);
    }

    /**
     * D'où vient le coût d'achat : « bon », « fiche » ou « cmup ».
     * L'écran l'affiche pour qu'un chiffre ne soit jamais sans origine.
     */
    public function purchaseCostSource(Product $product): string
    {
        $dernier = DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as r', 'r.id', '=', 'l.goods_receipt_id')
            ->where('l.product_id', $product->id)
            ->orderByDesc('r.received_at')
            ->orderByDesc('l.id')
            ->value('l.unit_price');

        if ($dernier !== null && (float) $dernier > 0) {
            return 'bon';
        }

        return (float) $product->cost_price > 0 ? 'fiche' : 'cmup';
    }

    /**
     * CMUP : valeur du stock détenu rapportée à sa quantité. Sert à valoriser
     * un inventaire ou un mouvement, pas à décider d'un prix de vente.
     */
    public function unitCost(Product $product): float
    {
        /** @var object{q: int|null, v: float|null}|null $row */
        $row = Stock::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->selectRaw('SUM(quantity) as q, SUM(quantity * average_cost) as v')
            ->first();

        $quantity = (int) ($row->q ?? 0);
        $value = (float) ($row->v ?? 0);

        if ($quantity > 0) {
            return round($value / $quantity, 2);
        }

        return (float) $product->cost_price;
    }
}
