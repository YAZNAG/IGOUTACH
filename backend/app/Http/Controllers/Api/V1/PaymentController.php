<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Payments\Actions\DeclareChequeAction;
use App\Domain\Payments\Models\Cheque;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Encaissements clients, cycle des chèques, balance âgée et relevé client.
 */
final class PaymentController extends Controller
{
    /**
     * Vue globale = voit les clients et règlements de tous les lieux.
     */
    private function hasGlobalView(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && ($user->can('customer.view_all') || $user->can('stock.view_global'));
    }

    /**
     * Clients visibles par l'utilisateur : ceux qu'il a créés.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Customer>
     */
    private function visibleCustomerIds(Request $request)
    {
        return Customer::query()->select('id')->where('created_by', $request->user()?->id);
    }

    /**
     * Le lieu auquel l'utilisateur est rattaché, ou 0 s'il voit tout.
     *
     * Le crédit se compte par lieu : un responsable ne doit voir que la dette
     * née des ventes de son propre point de vente, pas celle contractée par le
     * même client ailleurs.
     */
    private function lieuDeLUtilisateur(Request $request): int
    {
        if ($this->hasGlobalView($request)) {
            return 0;
        }

        return (int) ($request->user()?->getAttribute('warehouse_id') ?? 0);
    }

    public function index(Request $request): JsonResponse
    {
        // Les mêmes restrictions doivent porter sur la page et sur les
        // totaux : un total calculé sans le cloisonnement laisserait un
        // vendeur deviner ce qu'encaissent les autres.
        $filtres = fn ($q) => $q
            ->when($request->integer('customer_id') > 0, fn ($x) => $x->where('customer_id', $request->integer('customer_id')))
            ->when($request->integer('payment_method_id') > 0, fn ($x) => $x->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->string('date_from')->isNotEmpty(), fn ($x) => $x->whereDate('received_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($x) => $x->whereDate('received_at', '<=', $request->string('date_to')->value()))
            // Cloisonnement : sans vue globale, on ne voit que les règlements
            // de ses propres clients (les paiements n'ont pas de lieu propre).
            ->when(! $this->hasGlobalView($request), fn ($x) => $x->whereIn('customer_id', $this->visibleCustomerIds($request)));

        $perPage = in_array($request->integer('per_page', 20), [20, 50, 100], true)
            ? $request->integer('per_page', 20)
            : 20;

        $payments = $filtres(Payment::query()->with(['customer:id,code,name', 'method:id,name']))
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $totaux = $filtres(Payment::query())
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(amount), 0) as montant')
            ->first();

        $payments->through(fn (Payment $p): array => [
            'id' => $p->id,
            'reference' => $p->reference,
            'customer' => $p->customer?->name,
            'method' => $p->method?->name,
            'amount' => (float) $p->amount,
            'cheque_status' => $p->cheque_status,
            'cheque_reference' => $p->cheque_reference,
            'received_at' => $p->received_at->format('Y-m-d'),
            // `received_at` est une colonne DATE : elle ne porte pas
            // d'heure. L'heure d'encaissement est celle de la saisie.
            'created_at' => $p->created_at?->format('Y-m-d H:i'),
            // Sans cette URL, le justificatif serait enregistre sans jamais
            // pouvoir etre relu.
            'receipt_url' => $p->receipt_path !== null
                ? Storage::disk('public')->url($p->receipt_path)
                : null,
        ]);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                // Somme encaissée sur toute la période filtrée, pas sur la
                // page : c'est le chiffre qu'on vient chercher.
                'total_amount' => round((float) ($totaux?->getAttribute('montant') ?? 0), 2),
            ],
        ]);
    }

    /**
     * Supprime une écriture du relevé client.
     *
     * Réservée aux écritures saisies à la main. Une écriture adossée à une
     * facture, un règlement ou un retour est le reflet d'un document : la
     * supprimer laisserait le document en place et le relevé ne
     * correspondrait plus à rien. Ces cas se défont en annulant le document
     * lui-même, qui rend son écriture inverse.
     *
     * Les soldes cumulés suivants sont recalculés : sinon le relevé
     * afficherait des lignes qui ne s'enchaînent plus.
     */
    public function destroyLedgerEntry(CustomerLedgerEntry $entry, CustomerLedger $ledger): JsonResponse
    {
        if ($entry->reference_type !== null) {
            $origine = match (true) {
                str_contains((string) $entry->reference_type, 'Sale') => 'une vente',
                str_contains((string) $entry->reference_type, 'Payment') => 'un règlement',
                default => 'un document',
            };

            return response()->json([
                'message' => "Cette écriture provient d'{$origine} : elle ne peut pas être supprimée seule. "
                    .'Annulez le document, son écriture inverse suivra.',
            ], 422);
        }

        $ledger->supprimerEcriture($entry);

        return response()->json(null, 204);
    }

    /**
     * Factures d'un client encore dues, de la plus ancienne à la plus récente.
     *
     * L'ordre est celui du règlement usuel : on solde d'abord ce qui traîne.
     * Les brouillons et les annulées sont exclus — ils ne doivent rien.
     */
    public function openInvoices(Request $request, Customer $customer): JsonResponse
    {
        // Un responsable n'encaisse que sur les factures de son lieu : lui
        // proposer celles d'un autre point de vente l'amenerait a solder une
        // dette qui ne le concerne pas, et a crediter la mauvaise caisse.
        $lieu = $this->lieuDeLUtilisateur($request);

        $factures = Sale::query()
            ->with('warehouse:id,code,name')
            ->where('customer_id', $customer->id)
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereRaw('paid_amount < total')
            ->when($lieu > 0, fn ($q) => $q->where('warehouse_id', $lieu))
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $factures->map(fn (Sale $s): array => [
            'id' => $s->id,
            'reference' => $s->reference,
            'total' => (float) $s->total,
            'paid_amount' => (float) $s->paid_amount,
            'remaining' => round((float) $s->total - (float) $s->paid_amount, 2),
            'payment_status' => $s->payment_status,
            'date' => $s->created_at?->format('Y-m-d'),
            'warehouse' => $s->warehouse?->code,
            'warehouse_name' => $s->warehouse?->name,
        ])->values()->all()]);
    }

    /**
     * Contrôle de cohérence d'un règlement ventilé.
     *
     * @param  array{customer_id: int, amount: float, allocations?: list<array{sale_id: int, amount: float}>}  $data
     * @return string|null  message d'erreur, ou null si tout est cohérent
     */
    private function verifierVentilations(array $data): ?string
    {
        $ventilations = $data['allocations'] ?? [];

        $identifiants = array_column($ventilations, 'sale_id');
        if (count($identifiants) !== count(array_unique($identifiants))) {
            return 'Une même facture est indiquée deux fois.';
        }

        // Une facture d'un autre client réduirait l'encours du mauvais compte.
        $etrangeres = Sale::query()
            ->whereIn('id', $identifiants)
            ->where(fn ($q) => $q->where('customer_id', '!=', $data['customer_id'])->orWhereNull('customer_id'))
            ->pluck('reference');

        if ($etrangeres->isNotEmpty()) {
            return 'Ces factures n’appartiennent pas à ce client : '.$etrangeres->implode(', ').'.';
        }

        $somme = round(array_sum(array_column($ventilations, 'amount')), 2);
        if (abs($somme - round((float) $data['amount'], 2)) > 0.001) {
            return sprintf(
                'La répartition (%.2f DH) ne correspond pas au montant encaissé (%.2f DH).',
                $somme,
                (float) $data['amount'],
            );
        }

        return null;
    }

    public function store(Request $request, RecordPaymentAction $action, DeclareChequeAction $declarer): JsonResponse
    {
        /** @var array{customer_id: int, amount: float, payment_method_id?: int|null, sale_id?: int|null, cash_session_id?: int|null, cheque_reference?: string|null, received_at: string, note?: string|null, cheque?: array{instrument?: string|null, number: string, cheque_date: string, bank?: string|null, origin: string, drawer_name?: string|null}} $data */
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            // Le mode est obligatoire : un encaissement sans mode ne se
            // rapproche d'aucune caisse ni d'aucun compte. Trois règlements
            // ainsi enregistrés ont suffi à rendre un compte client
            // incompréhensible — l'argent était là, personne ne savait où.
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'cash_session_id' => ['nullable', 'integer', 'exists:cash_sessions,id'],
            'cheque_reference' => ['nullable', 'string', 'max:60'],
            'cheque_id' => ['nullable', 'integer', 'exists:cheques,id'],
            'received_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            // Justificatif facultatif : l'avis de virement, le plus souvent.
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Règlement ventilé sur plusieurs factures du même client.
            'allocations' => ['sometimes', 'array', 'min:1'],
            'allocations.*.sale_id' => ['required', 'integer', 'exists:sales,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
            // Effet remis par le client, déclaré au fil de l'encaissement. Le
            // chèque peut être le sien ou celui d'un tiers.
            ...DeclareChequeAction::reglesImbriquees([
                Cheque::ORIGIN_CUSTOMER,
                Cheque::ORIGIN_THIRD_PARTY,
            ]),
        ]);

        if (isset($data['allocations'])) {
            $erreur = $this->verifierVentilations($data);
            if ($erreur !== null) {
                return response()->json(['message' => $erreur], 422);
            }
        }

        // L'effet est créé avant le règlement : il doit exister pour être
        // référencé, et il reste au portefeuille même si l'encaissement échoue.
        if (isset($data['cheque'])) {
            try {
                $cheque = $declarer->execute(
                    donnees: $data['cheque'],
                    direction: Cheque::DIRECTION_IN,
                    montant: (float) $data['amount'],
                    customerId: (int) $data['customer_id'],
                    createdBy: $request->user()?->id,
                );
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $data['cheque_id'] = $cheque->id;
            $data['cheque_reference'] ??= $cheque->number;
        }

        unset($data['cheque']);

        if ($request->hasFile('receipt')) {
            /** @var UploadedFile $fichier */
            $fichier = $request->file('receipt');
            $data['receipt_path'] = (string) $fichier->store('payments', 'public');
        }

        unset($data['receipt']);

        try {
            $payment = $action->execute($data, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $payment->id, 'reference' => $payment->reference]], 201);
    }

    /**
     * Cycle du chèque : received → deposited → cleared | bounced.
     */
    public function chequeStatus(Request $request, Payment $payment): JsonResponse
    {
        /** @var array{status: string} $data */
        $data = $request->validate([
            'status' => ['required', 'in:deposited,cleared,bounced'],
        ]);

        if ($payment->cheque_status === null) {
            return response()->json(['message' => "Cet encaissement n'est pas un chèque."], 422);
        }

        $payment->update(['cheque_status' => $data['status']]);

        return response()->json(['data' => ['id' => $payment->id, 'cheque_status' => $payment->cheque_status]]);
    }

    /**
     * Balance âgée : encours par tranches d'ancienneté des factures impayées.
     */
    public function aging(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $lieu = $this->lieuDeLUtilisateur($request);

        // Par defaut, la page ne montre que ceux qui doivent : c'est une liste
        // de creances. Avec « all », elle liste tout le fichier client, chacun
        // avec le du de MON lieu — pour verifier ce qu'un client donne doit,
        // meme s'il est a jour. Une recherche accompagne alors la demande,
        // sinon on deroule des centaines de lignes a zero.
        $tous = $request->boolean('all');
        $recherche = $request->string('q')->value();

        $rows = Sale::query()
            ->with(['customer:id,code,name', 'warehouse:id,code,name'])
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->where('payment_status', '!=', 'paid')
            // Cloisonnement par lieu : un responsable voit tous les clients,
            // mais seulement ce qu'ils doivent sur SES ventes. Le meme client
            // peut devoir ailleurs sans que cela le regarde.
            ->when($lieu > 0, fn ($q) => $q->where('warehouse_id', $lieu))
            ->get()
            ->groupBy('customer_id')
            ->map(function ($sales) {
                $first = $sales->first();
                $buckets = ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'over60' => 0.0];
                $parLieu = [];

                foreach ($sales as $sale) {
                    $due = (float) $sale->total - (float) $sale->paid_amount;
                    $age = $sale->confirmed_at !== null ? (int) $sale->confirmed_at->diffInDays(now()) : 0;
                    $key = $age <= 30 ? 'current' : ($age <= 60 ? 'b30' : ($age <= 90 ? 'b60' : 'over60'));
                    $buckets[$key] += $due;

                    // La ventilation par lieu : c'est elle qui permet a chacun
                    // de savoir quelle part de la dette le concerne.
                    $code = $sale->warehouse?->code ?? 'Sans lieu';
                    if (! isset($parLieu[$code])) {
                        $parLieu[$code] = [
                            'warehouse_id' => $sale->warehouse_id,
                            'code' => $code,
                            'name' => $sale->warehouse?->name,
                            'due' => 0.0,
                            'invoices' => 0,
                        ];
                    }
                    $parLieu[$code]['due'] += $due;
                    $parLieu[$code]['invoices']++;
                }

                $total = $buckets['current'] + $buckets['b30'] + $buckets['b60'] + $buckets['over60'];

                return [
                    'customer_id' => $first?->customer?->id,
                    'customer' => $first?->customer?->name,
                    'customer_code' => $first?->customer?->code,
                    'bucket_0_30' => round($buckets['current'], 2),
                    'bucket_31_60' => round($buckets['b30'], 2),
                    'bucket_61_90' => round($buckets['b60'], 2),
                    'bucket_over_90' => round($buckets['over60'], 2),
                    'total_due' => round($total, 2),
                    'invoices' => $sales->count(),
                    'by_warehouse' => array_values(array_map(static function (array $l): array {
                        $l['due'] = round($l['due'], 2);

                        return $l;
                    }, $parLieu)),
                ];
            })
            ->sortByDesc('total_due')
            ->values();

        // Les clients sans dette, ajoutes en fin de liste quand on les demande.
        // Ils portent les memes cles, a zero : l'ecran n'a pas deux formes de
        // ligne a distinguer.
        if ($tous) {
            $dejaLa = $rows->pluck('customer_id')->filter()->all();

            $autres = Customer::query()
                ->whereNotIn('id', $dejaLa === [] ? [0] : $dejaLa)
                ->when($recherche !== '', fn ($q) => $q->where(
                    fn ($sub) => $sub->where('name', 'like', "%{$recherche}%")
                        ->orWhere('code', 'like', "%{$recherche}%")
                        ->orWhere('phone', 'like', "%{$recherche}%"),
                ))
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'code', 'name'])
                ->map(fn (Customer $c): array => [
                    'customer_id' => $c->id,
                    'customer' => $c->name,
                    'customer_code' => $c->code,
                    'bucket_0_30' => 0.0,
                    'bucket_31_60' => 0.0,
                    'bucket_61_90' => 0.0,
                    'bucket_over_90' => 0.0,
                    'total_due' => 0.0,
                    'invoices' => 0,
                    'by_warehouse' => [],
                ]);

            // La recherche filtre aussi ceux qui doivent : sans cela, taper un
            // nom laisserait toute la liste des debiteurs en tete.
            if ($recherche !== '') {
                $terme = mb_strtolower($recherche);
                $rows = $rows->filter(fn (array $l): bool => str_contains(
                    mb_strtolower((string) $l['customer'].' '.(string) $l['customer_code']),
                    $terme,
                ))->values();
            }

            $rows = $rows->concat($autres)->values();
        }

        // Le total par lieu, tous clients confondus : ce que chaque point de
        // vente a laisse dehors.
        $parLieu = [];
        foreach ($rows as $ligne) {
            foreach ($ligne['by_warehouse'] as $l) {
                $code = $l['code'];
                $parLieu[$code] = ($parLieu[$code] ?? 0) + $l['due'];
            }
        }
        arsort($parLieu);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'scoped_warehouse_id' => $lieu > 0 ? $lieu : null,
                'total_due' => round($rows->sum('total_due'), 2),
                'by_warehouse' => array_map(
                    static fn (string $code, float $du): array => ['code' => $code, 'due' => round($du, 2)],
                    array_keys($parLieu),
                    array_values($parLieu),
                ),
            ],
        ]);
    }

    /**
     * Relevé client : écritures du grand-livre, plus récentes en premier.
     */
    public function statement(Request $request, Customer $customer): JsonResponse
    {
        // Tout le monde peut consulter n'importe quel client : c'est le
        // montant, pas la fiche, qui se cloisonne. Le releve d'un responsable
        // est reconstruit sur ses seules factures, avec son propre cumul —
        // filtrer les lignes en gardant le solde global afficherait une
        // colonne qui ne s'enchaine plus.
        $lieu = $this->lieuDeLUtilisateur($request);

        // Le du du client, ventile par lieu : la reponse a « combien me
        // doit-il, a moi ». Toujours calcule, meme pour l'administrateur, qui
        // a besoin de la repartition.
        $parLieu = Sale::query()
            ->with('warehouse:id,code,name')
            ->where('customer_id', $customer->id)
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereRaw('paid_amount < total')
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($ventes): array => [
                'warehouse_id' => $ventes->first()?->warehouse_id,
                'code' => $ventes->first()?->warehouse?->code ?? 'Sans lieu',
                'name' => $ventes->first()?->warehouse?->name,
                'due' => round($ventes->sum(fn ($v) => (float) $v->total - (float) $v->paid_amount), 2),
                'invoices' => $ventes->count(),
            ])
            ->sortByDesc('due')
            ->values();

        if ($lieu > 0) {
            // Releve d'un responsable : reconstruit sur ses seules factures et
            // les reglements qui les ont soldees, avec son propre cumul. Le
            // grand-livre global melangerait les lieux, et son solde ne
            // correspondrait a rien de ce qu'il peut encaisser.
            $sien = $parLieu->firstWhere('warehouse_id', $lieu);

            $lignes = collect();

            foreach (Sale::query()
                ->where('customer_id', $customer->id)
                ->where('type', Sale::TYPE_INVOICE)
                ->where('status', Sale::STATUS_CONFIRMED)
                ->where('warehouse_id', $lieu)
                ->orderBy('confirmed_at')
                ->get() as $vente) {
                $lignes->push([
                    'date' => $vente->confirmed_at?->format('Y-m-d H:i'),
                    'type' => 'invoice',
                    'amount' => (float) $vente->total,
                    'note' => $vente->reference,
                    'from_document' => true,
                ]);

                foreach (DB::table('payment_allocations as a')
                    ->join('payments as p', 'p.id', '=', 'a.payment_id')
                    ->where('a.sale_id', $vente->id)
                    ->orderBy('p.received_at')
                    ->get(['p.reference', 'a.amount', 'p.received_at', 'p.created_at']) as $r) {
                    $lignes->push([
                        'date' => (string) $r->created_at,
                        'type' => 'payment',
                        'amount' => -1 * (float) $r->amount,
                        'note' => (string) $r->reference,
                        'from_document' => true,
                    ]);
                }
            }

            // Le cumul est recalcule sur ce sous-ensemble : c'est ce que le
            // client doit a CE lieu, pas son solde global.
            $cumul = 0.0;
            $lignes = $lignes->sortBy('date')->values()->map(function (array $l) use (&$cumul): array {
                $cumul = round($cumul + $l['amount'], 2);
                $l['balance_after'] = $cumul;

                return $l;
            });

            return response()->json(['data' => [
                'customer' => ['id' => $customer->id, 'code' => $customer->code, 'name' => $customer->name],
                // Le solde affiche est celui du lieu, pas le solde global.
                'balance' => $sien['due'] ?? 0.0,
                'ledger_balance' => round((float) $customer->balance, 2),
                'global_balance' => (float) $customer->balance,
                'scoped_warehouse_id' => $lieu,
                'credit_limit' => (float) $customer->credit_limit,
                'is_blocked' => $customer->is_blocked,
                'by_warehouse' => $sien !== null ? [$sien] : [],
                'entries' => $lignes->reverse()->values()->all(),
            ]]);
        }

        $entries = CustomerLedgerEntry::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (CustomerLedgerEntry $e): array => [
                'id' => $e->id,
                'date' => $e->created_at?->format('Y-m-d H:i'),
                'type' => $e->type,
                'amount' => (float) $e->amount,
                'balance_after' => (float) $e->balance_after,
                'note' => $e->note,
                // Une écriture adossée à un document ne se supprime pas seule :
                // l'interface n'a pas à proposer un bouton qui sera refusé.
                'from_document' => $e->reference_type !== null,
            ]);

        return response()->json(['data' => [
            'customer' => ['id' => $customer->id, 'code' => $customer->code, 'name' => $customer->name],
            // Le montant reellement du : la somme des factures non soldees.
            // Le solde du grand-livre s'en ecarte des qu'un reglement n'a ete
            // impute a aucune facture — il peut alors passer negatif alors que
            // des factures restent dues. C'est le du sur factures qui se
            // reclame, donc c'est lui que l'ecran met en avant.
            'balance' => round((float) $parLieu->sum('due'), 2),
            'ledger_balance' => round((float) $customer->balance, 2),
            'global_balance' => round((float) $parLieu->sum('due'), 2),
            'scoped_warehouse_id' => null,
            'credit_limit' => (float) $customer->credit_limit,
            'is_blocked' => $customer->is_blocked,
            'by_warehouse' => $parLieu->all(),
            'entries' => $entries->values()->all(),
        ]]);
    }
}
