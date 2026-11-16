<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Services\RetourClient;
use App\Http\Controllers\Concerns\CloisonneParLieu;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Retours clients : ce qui précède la saisie, c'est-à-dire retrouver la
 * vente que l'article vient de quitter.
 */
final class CustomerReturnController extends Controller
{
    use CloisonneParLieu;

    /**
     * Les ventes qui contiennent l'article, de la plus récente à la plus
     * ancienne, avec ce qu'il reste à retourner sur chacune.
     * GET /customer-returns/sales?product_id=&warehouse_id=
     */
    public function sales(Request $request, RetourClient $retours): JsonResponse
    {
        /** @var array{product_id: int, warehouse_id?: int|null} $data */
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        // Hors direction, seules les ventes de son lieu : les clients des
        // autres lieux ne se voient pas, leurs ventes non plus.
        $lieu = $this->lieuCloisonne($request);
        if ($lieu === 0) {
            $lieu = (int) ($data['warehouse_id'] ?? 0);
        }

        return response()->json(['data' => $retours->ventesAvecArticle((int) $data['product_id'], $lieu)]);
    }
}
