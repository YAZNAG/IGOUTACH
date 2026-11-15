<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Domain\Customers\Models\Customer;
use Illuminate\Http\Request;

/**
 * Cloisonnement par lieu : hors de la direction, chacun ne voit que les
 * clients (et ce qui en découle) de son propre point de vente.
 */
trait CloisonneParLieu
{
    /** La direction voit tout : vue globale du stock ou de tous les clients. */
    protected function voitTousLesLieux(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && ($user->can('customer.view_all') || $user->can('stock.view_global'));
    }

    /** Le lieu auquel l'utilisateur est rattaché, ou 0 s'il voit tout. */
    protected function lieuCloisonne(Request $request): int
    {
        if ($this->voitTousLesLieux($request)) {
            return 0;
        }

        return (int) ($request->user()?->getAttribute('warehouse_id') ?? 0);
    }

    /** Refuse (403) une fiche d'un autre lieu à qui n'a pas la vue globale. */
    protected function refuserHorsLieu(Request $request, ?Customer $customer): void
    {
        $lieu = $this->lieuCloisonne($request);

        if ($customer !== null && $lieu > 0 && (int) $customer->warehouse_id !== $lieu) {
            abort(403, 'Ce client appartient à un autre lieu.');
        }
    }
}
