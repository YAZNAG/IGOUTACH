<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Services\ReleveCreditClient;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Relevé de crédit imprimable d'un client.
 * GET /customers/{customer}/credit-pdf?warehouse_id=
 *
 * Même périmètre que la page des crédits : un responsable n'imprime que la
 * dette née dans son lieu ; la direction imprime tout, ou un seul lieu.
 */
final class CustomerCreditPdfController extends Controller
{
    public function __invoke(Request $request, Customer $customer, ReleveCreditClient $releve): Response
    {
        $user = $request->user();
        $voitTout = $user !== null && ($user->can('customer.view_all') || $user->can('stock.view_global'));
        $lieu = $voitTout ? $request->integer('warehouse_id') : (int) ($user?->getAttribute('warehouse_id') ?? 0);

        if (! $voitTout && $lieu > 0 && (int) $customer->warehouse_id !== $lieu) {
            abort(403, 'Ce client appartient à un autre lieu.');
        }

        $donnees = $releve->pour($customer, $lieu);

        $nom = 'credit-'.($customer->code ?: $customer->id).'.pdf';

        return Pdf::loadView('pdf.customer-credit', $donnees)->setPaper('a4')->download($nom);
    }
}
