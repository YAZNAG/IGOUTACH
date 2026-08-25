<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Prix d'achat d'un article, et l'historique qui le justifie.
 *
 * Le prix d'achat de la fiche est une déclaration ; les réceptions sont des
 * faits. Les montrer ensemble est le seul moyen de voir qu'une fiche est
 * restée sur un prix que le fournisseur ne pratique plus.
 */
final class PurchasePriceController extends Controller
{
    /**
     * Coût réellement utilisé par l'application pour un article.
     *
     * C'est le coût moyen unitaire pondéré, calculé sur le stock détenu tous
     * lieux confondus. Sans stock, il n'y a pas de moyenne à faire : on
     * retombe sur le prix d'achat de la fiche.
     *
     * @return array{cost: float, source: string, quantity: int}
     */
    private function coutUtilise(Product $product): array
    {
        $s = DB::table('stocks')
            ->selectRaw('SUM(quantity) as q, SUM(quantity * average_cost) as v')
            ->where('product_id', $product->id)
            ->first();

        $quantite = (int) ($s->q ?? 0);
        $valeur = (float) ($s->v ?? 0);

        if ($quantite > 0) {
            return [
                'cost' => round($valeur / $quantite, 2),
                'source' => 'cmup',
                'quantity' => $quantite,
            ];
        }

        return [
            'cost' => round((float) ($product->cost_price ?? 0), 2),
            'source' => 'purchase_price',
            'quantity' => 0,
        ];
    }

    /**
     * Historique des réceptions d'un article : qui a livré, quand, à quel prix.
     *
     * GET /products/{product}/purchase-history
     */
    public function history(Product $product): JsonResponse
    {
        $lignes = DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as r', 'r.id', '=', 'l.goods_receipt_id')
            ->leftJoin('suppliers as f', 'f.id', '=', 'r.supplier_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id')
            ->where('l.product_id', $product->id)
            ->orderByDesc('r.received_at')
            ->limit(50)
            ->get([
                'r.number as reference',
                'r.received_at as date',
                'f.name as fournisseur',
                'w.code as lieu',
                'l.quantity as qte',
                'l.unit_price as prix',
            ]);

        return response()->json(['data' => [
            'purchase_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
            'cost' => $this->coutUtilise($product),
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'history' => $lignes->map(fn ($r): array => [
                'reference' => (string) $r->reference,
                'date' => substr((string) $r->date, 0, 10),
                'supplier' => $r->fournisseur !== null ? (string) $r->fournisseur : null,
                'warehouse' => $r->lieu !== null ? (string) $r->lieu : null,
                'quantity' => (int) $r->qte,
                'unit_price' => round((float) $r->prix, 2),
                'line_total' => round((float) $r->prix * (int) $r->qte, 2),
            ])->values()->all(),
        ]]);
    }

    /**
     * Change le prix d'achat d'un article.
     *
     * PATCH /products/{product}/purchase-price
     *
     * Vendre en dessous du coût est refusé ici comme ailleurs : relever le
     * prix d'achat au-dessus du prix de vente reviendrait à vendre à perte
     * sans que personne ne l'ait décidé. On refuse plutôt que d'ajuster le
     * prix de vente en douce — c'est une décision commerciale, pas une
     * conséquence technique.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        /** @var array{purchase_price: float} $data */
        $data = $request->validate([
            'purchase_price' => ['required', 'numeric', 'min:0'],
        ]);

        $nouveau = round((float) $data['purchase_price'], 2);
        $vente = (float) ($product->sale_price ?? 0);

        // Le coût de référence est le CMUP quand il existe ; sinon le prix
        // d'achat que l'on est en train de poser.
        $etat = $this->coutUtilise($product);
        $cout = $etat['source'] === 'cmup' ? $etat['cost'] : $nouveau;

        if ($vente > 0 && $vente < $cout) {
            return response()->json([
                'message' => sprintf(
                    'Le prix de vente (%s DH) est inférieur au coût de l’article (%s DH) : '
                    .'cette vente se ferait à perte. Ajustez d’abord le prix de vente.',
                    number_format($vente, 2, ',', ' '),
                    number_format($cout, 2, ',', ' '),
                ),
                'cost' => $cout,
                'sale_price' => $vente,
            ], 422);
        }

        $ancien = $product->cost_price !== null ? (float) $product->cost_price : null;
        $product->update(['cost_price' => $nouveau]);

        return response()->json(['data' => [
            'id' => $product->id,
            'sku' => $product->sku,
            'previous_purchase_price' => $ancien,
            'purchase_price' => $nouveau,
            'cost' => $this->coutUtilise($product->refresh()),
            'sale_price' => $vente,
        ]]);
    }
}
