<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\PaymentAllocation;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use Illuminate\Support\Carbon;

/**
 * Relevé de crédit d'un client : les factures qu'il doit encore, avec les
 * articles emportés sur chacune et les règlements déjà reçus dessus.
 *
 * C'est le document qu'on remet au client (ou qu'on garde sous la main)
 * quand on lui réclame son dû : il ne se contente pas d'un montant, il
 * raconte d'où vient la dette, article par article. Le périmètre suit celui
 * de la page des crédits : un responsable ne voit que les factures de son
 * lieu, la direction peut se limiter à un lieu ou tout prendre.
 */
final class ReleveCreditClient
{
    /**
     * @return array{
     *     client: array{code: string|null, name: string, phone: string|null, address: string|null, city: string|null, credit_limit: float, is_blocked: bool},
     *     lieu: string|null,
     *     factures: list<array<string, mixed>>,
     *     totaux: array{facture: float, paye: float, du: float, articles: int, unites: int},
     *     anciennete: array{j0_30: float, j31_60: float, j61_90: float, plus_90: float},
     *     par_lieu: list<array{code: string, du: float, factures: int}>,
     *     edite_le: string
     * }
     */
    public function pour(Customer $client, int $lieu = 0): array
    {
        $ventes = Sale::query()
            ->with([
                'warehouse:id,code,name',
                'lines' => fn ($q) => $q->with('product:id,sku,name')->orderBy('id'),
            ])
            ->where('customer_id', $client->id)
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereRaw('paid_amount < total')
            ->when($lieu > 0, fn ($q) => $q->where('warehouse_id', $lieu))
            ->orderBy('confirmed_at')
            ->orderBy('id')
            ->get();

        // Les règlements imputés, facture par facture : ils disent au client
        // ce qui a déjà été pris en compte, et évitent de réclamer deux fois.
        $reglements = PaymentAllocation::query()
            ->whereIn('payment_allocations.sale_id', $ventes->pluck('id'))
            ->join('payments as p', 'p.id', '=', 'payment_allocations.payment_id')
            ->orderBy('p.received_at')
            ->orderBy('p.id')
            ->get(['payment_allocations.sale_id', 'payment_allocations.amount', 'p.reference', 'p.received_at'])
            ->groupBy('sale_id');

        $anciennete = ['j0_30' => 0.0, 'j31_60' => 0.0, 'j61_90' => 0.0, 'plus_90' => 0.0];
        $parLieu = [];
        $articles = 0;
        $unites = 0;
        $maintenant = Carbon::now();

        $factures = $ventes->map(function (Sale $vente) use ($reglements, &$anciennete, &$parLieu, &$articles, &$unites, $maintenant): array {
            $reste = round((float) $vente->total - (float) $vente->paid_amount, 2);
            $date = $vente->confirmed_at ?? $vente->created_at;
            $age = $date !== null ? (int) $date->diffInDays($maintenant) : 0;

            $tranche = $age <= 30 ? 'j0_30' : ($age <= 60 ? 'j31_60' : ($age <= 90 ? 'j61_90' : 'plus_90'));
            $anciennete[$tranche] += $reste;

            $code = $vente->warehouse?->code ?? 'Sans lieu';
            $parLieu[$code] ??= ['code' => $code, 'du' => 0.0, 'factures' => 0];
            $parLieu[$code]['du'] += $reste;
            $parLieu[$code]['factures']++;

            $lignes = $vente->lines->map(function (SaleLine $l) use (&$articles, &$unites): array {
                $articles++;
                $unites += (int) $l->quantity;

                return [
                    'sku' => $l->product?->sku ?? '—',
                    'nom' => $l->product?->name ?? 'Article supprimé',
                    'quantite' => (int) $l->quantity,
                    'pu' => round((float) $l->unit_price, 2),
                    'total' => round((float) $l->line_total, 2),
                ];
            })->values()->all();

            $recus = ($reglements->get($vente->id) ?? collect())->map(fn ($r): array => [
                'reference' => (string) $r->reference,
                'date' => $r->received_at !== null ? Carbon::parse((string) $r->received_at)->format('d/m/Y') : '—',
                'montant' => round((float) $r->amount, 2),
            ])->values()->all();

            return [
                'reference' => $vente->reference,
                'date' => $date?->format('d/m/Y') ?? '—',
                'age' => $age,
                'lieu' => $code,
                'total' => round((float) $vente->total, 2),
                'paye' => round((float) $vente->paid_amount, 2),
                'reste' => $reste,
                'lignes' => $lignes,
                'reglements' => $recus,
            ];
        })->values()->all();

        $totalFacture = round(array_sum(array_column($factures, 'total')), 2);
        $totalPaye = round(array_sum(array_column($factures, 'paye')), 2);

        return [
            'client' => [
                'code' => $client->code,
                'name' => $client->name,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'credit_limit' => round((float) $client->credit_limit, 2),
                'is_blocked' => (bool) $client->is_blocked,
            ],
            'lieu' => $lieu > 0 ? ($ventes->first()?->warehouse?->code ?? null) : null,
            'factures' => $factures,
            'totaux' => [
                'facture' => $totalFacture,
                'paye' => $totalPaye,
                'du' => round($totalFacture - $totalPaye, 2),
                'articles' => $articles,
                'unites' => $unites,
            ],
            'anciennete' => array_map(static fn (float $v): float => round($v, 2), $anciennete),
            'par_lieu' => array_values(array_map(static function (array $l): array {
                $l['du'] = round($l['du'], 2);

                return $l;
            }, $parLieu)),
            'edite_le' => $maintenant->format('d/m/Y H:i'),
        ];
    }
}
