<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetCreditLimitRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Support\Query\Sortable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Domain\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;

final class CustomerController extends Controller
{
    private function perPage(Request $request): int
    {
        $requested = $request->integer('per_page', 20);

        return in_array($requested, [20, 50, 100], true) ? $requested : 20;
    }

    /**
     * Le lieu auquel l'utilisateur est rattaché, ou 0 s'il voit tout.
     */
    private function lieuDeLUtilisateur(Request $request): int
    {
        $user = $request->user();

        if ($user === null || $user->can('customer.view_all') || $user->can('stock.view_global')) {
            return 0;
        }

        return (int) ($user->getAttribute('warehouse_id') ?? 0);
    }

    /**
     * Reste dû par client sur les factures d'un lieu.
     *
     * @param  list<int>  $clients
     * @return array<int, float>
     */
    private function dusParClient(int $lieu, array $clients): array
    {
        if ($clients === []) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('sales')
            ->where('type', 'invoice')->where('status', 'confirmed')
            ->where('warehouse_id', $lieu)
            ->whereIn('customer_id', $clients)
            ->whereRaw('paid_amount < total')
            ->groupBy('customer_id')
            ->pluck(\Illuminate\Support\Facades\DB::raw('ROUND(SUM(total - paid_amount), 2)'), 'customer_id')
            ->map(fn ($v): float => (float) $v)
            ->all();
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // Le fichier client est commun : un responsable qui ne voit pas un
        // client le recree en double, et le meme acheteur finit avec deux
        // fiches et deux dettes. C'est le MONTANT qui se cloisonne, pas la
        // fiche — voir « lieuDeLUtilisateur » plus bas.
        $query = Customer::query()
            ->when($request->string('q')->isNotEmpty(), function ($q) use ($request) {
                $term = $request->string('q')->value();
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->has('is_blocked'), fn ($q) => $q->where('is_blocked', $request->boolean('is_blocked')));

        Sortable::apply($query, $request, [
            'code' => 'code',
            'name' => 'name',
            'city' => 'city',
            'balance' => 'balance',
            'credit_limit' => 'credit_limit',
        ], 'name');

        $page = $query->paginate($this->perPage($request));

        // Sans vue globale, le solde affiche est celui du lieu : montrer le
        // solde global reviendrait a exposer la dette contractee ailleurs.
        $lieu = $this->lieuDeLUtilisateur($request);
        if ($lieu > 0) {
            $dus = $this->dusParClient($lieu, collect($page->items())->pluck('id')->all());

            foreach ($page->items() as $client) {
                $client->setAttribute('balance', $dus[$client->id] ?? 0.0);
            }
        }

        return CustomerResource::collection($page);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Le plafond de crédit relève d'une permission dédiée : il ne peut pas
        // être défini au détour d'une création par qui ne la possède pas.
        if (! ($request->user()?->can('customer.set_credit_limit') ?? false)) {
            unset($data['credit_limit']);
        }

        // Code auto-généré (CL-0001) si non fourni : le formulaire reste simple.
        if (! isset($data['code']) || $data['code'] === '') {
            $last = Customer::withTrashed()->where('code', 'like', 'CL-%')->orderByDesc('id')->value('code');
            $next = $last !== null ? ((int) substr((string) $last, 3)) + 1 : 1;
            $data['code'] = 'CL-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        }

        $data['created_by'] = $request->user()?->id;

        $customer = Customer::query()->create($data);

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->assertCanSee($request, $customer);

        // Même règle que la liste : sans vue globale, le solde affiché est
        // celui du lieu. Laisser le solde global ici rouvrait par la fiche la
        // porte que la liste venait de fermer.
        $lieu = $this->lieuDeLUtilisateur($request);
        if ($lieu > 0) {
            $customer->setAttribute('balance', $this->dusParClient($lieu, [$customer->id])[$customer->id] ?? 0.0);
        }

        return CustomerResource::make($customer->load(['priceType:id,name', 'seller:id,name', 'warehouse:id,code', 'createdBy:id,name']));
    }

    /**
     * Tout ce qui concerne ce client : identité, crédit, achats, règlements.
     *
     * Un seul appel : la fiche s'ouvre d'un coup au lieu d'enchaîner quatre
     * requêtes sur un téléphone en réseau lent.
     */
    public function overview(Request $request, Customer $customer): JsonResponse
    {
        $this->assertCanSee($request, $customer);

        $customer->load(['priceType:id,name', 'warehouse:id,code', 'createdBy:id,name']);

        $lieu = $this->lieuDeLUtilisateur($request);

        // Ce que le client doit, ventile par lieu. « Sale » porte le filtre de
        // lieu : un responsable n'obtient ici que sa propre part, un
        // administrateur la répartition complète.
        $parLieu = Sale::query()
            ->with('warehouse:id,code,name')
            ->where('customer_id', $customer->id)
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereRaw('paid_amount < total')
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($lignes): array => [
                'warehouse_id' => $lignes->first()?->warehouse_id,
                'code' => $lignes->first()?->warehouse?->code ?? 'Sans lieu',
                'due' => round($lignes->sum(fn ($v) => (float) $v->total - (float) $v->paid_amount), 2),
                'invoices' => $lignes->count(),
            ])
            ->sortByDesc('due')
            ->values();

        // L'encours affiché est la somme de ce qui précède, jamais le solde
        // global du client : un responsable n'a pas à voir la dette contractée
        // dans un autre point de vente, et encore moins à la réclamer.
        $encours = round((float) $parLieu->sum('due'), 2);

        // Les ventes sont deja cloisonnees par lieu et par vendeur : ce que
        // l'on voit ici est ce que l'on a le droit de voir ailleurs.
        $ventes = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('type', Sale::TYPE_INVOICE)
            ->orderByDesc('confirmed_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // Un règlement n'a pas de lieu en propre : c'est son imputation aux
        // factures qui le rattache à un point de vente. On ne retient donc que
        // la part imputée aux factures de MON lieu — un encaissement portant
        // sur les ventes d'un autre responsable ne me regarde pas, et l'afficher
        // en entier gonflerait le « réglé » d'une somme que je n'ai pas vue.
        $reglements = $lieu > 0
            ? DB::table('payment_allocations as a')
                ->join('payments as p', 'p.id', '=', 'a.payment_id')
                ->join('sales as v', 'v.id', '=', 'a.sale_id')
                ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                ->where('p.customer_id', $customer->id)
                ->where('v.warehouse_id', $lieu)
                ->orderByDesc('p.received_at')
                ->limit(50)
                ->select('p.id', 'p.reference', 'p.received_at', 'pm.name as mode')
                ->selectRaw('ROUND(SUM(a.amount), 2) as amount')
                ->groupBy('p.id', 'p.reference', 'p.received_at', 'pm.name')
                ->get()
            : DB::table('payments')
                ->leftJoin('payment_methods as pm', 'pm.id', '=', 'payments.payment_method_id')
                ->where('payments.customer_id', $customer->id)
                ->orderByDesc('payments.received_at')
                ->limit(50)
                ->select('payments.id', 'payments.reference', 'payments.amount', 'payments.received_at', 'pm.name as mode')
                ->get();

        $confirmees = $ventes->where('status', Sale::STATUS_CONFIRMED);
        $total = (float) $confirmees->sum('total');

        return response()->json(['data' => [
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'is_company' => (bool) $customer->is_company,
                'contact_name' => $customer->contact_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'city' => $customer->city,
                'ice' => $customer->ice,
                'price_type' => $customer->priceType?->name,
                'warehouse' => $customer->warehouse?->code,
                'created_by' => $customer->createdBy?->name,
                'notes' => $customer->notes,
                'is_active' => (bool) $customer->is_active,
            ],
            'credit' => [
                'balance' => $encours,
                'limit' => round((float) $customer->credit_limit, 2),
                'is_blocked' => (bool) $customer->is_blocked,
                // Part du plafond consommee : null quand aucun plafond n'est
                // fixe, sinon on afficherait une jauge qui ne veut rien dire.
                'usage_percent' => (float) $customer->credit_limit > 0
                    ? round($encours / (float) $customer->credit_limit * 100, 1)
                    : null,
                'unpaid_count' => $confirmees->where('payment_status', '!=', 'paid')->count(),
                // Le lieu sur lequel le montant est cadré, null pour qui voit
                // tout : l'écran sait alors s'il doit annoncer « vos ventes ».
                'scoped_warehouse_id' => $lieu > 0 ? $lieu : null,
                // La répartition, utile à l'administrateur qui doit savoir où
                // la créance a été laissée. Un responsable n'y trouve que sa
                // propre ligne.
                'by_warehouse' => $parLieu->all(),
            ],
            'stats' => [
                'sales_count' => $confirmees->count(),
                'total_purchased' => round($total, 2),
                'average_basket' => $confirmees->count() > 0 ? round($total / $confirmees->count(), 2) : 0.0,
                'last_purchase' => $confirmees->first()?->confirmed_at?->toDateString(),
                // La somme de ce qui est affiché juste au-dessus : un total
                // plus large ne se retrouverait dans aucune ligne.
                'total_paid' => round((float) $reglements->sum('amount'), 2),
            ],
            'sales' => $ventes->map(fn (Sale $v): array => [
                'id' => $v->id,
                'reference' => $v->reference,
                'date' => $v->confirmed_at?->toDateString() ?? $v->created_at?->toDateString(),
                'total' => round((float) $v->total, 2),
                'paid' => round((float) $v->paid_amount, 2),
                'remaining' => round((float) $v->total - (float) $v->paid_amount, 2),
                'status' => $v->status,
                'payment_status' => $v->payment_status,
            ])->values()->all(),
            'payments' => $reglements->map(fn ($p): array => [
                'id' => $p->id,
                'reference' => $p->reference,
                'amount' => round((float) $p->amount, 2),
                'date' => $p->received_at,
                'method' => $p->mode,
            ])->values()->all(),
        ]]);
    }

    /**
     * La fiche client est consultable par tous ceux qui ont « customer.view ».
     *
     * Le cloisonnement porte desormais sur le MONTANT, pas sur la fiche : un
     * responsable voit le client, et ne voit de sa dette que la part nee de
     * ses propres ventes. Refuser la fiche entiere le poussait a recreer le
     * client en double, et le meme acheteur se retrouvait avec deux comptes.
     */
    private function assertCanSee(Request $request, Customer $customer): void
    {
        // Aucune restriction : conserve comme point d'accroche si une regle
        // de visibilite devait revenir.
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer->refresh());
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'Client supprimé.']);
    }

    /**
     * Définit le plafond de crédit (permission dédiée).
     */
    public function setCreditLimit(SetCreditLimitRequest $request, Customer $customer): CustomerResource
    {
        /** @var array{credit_limit: int|float} $data */
        $data = $request->validated();
        $customer->update(['credit_limit' => $data['credit_limit']]);

        return CustomerResource::make($customer->refresh());
    }

    /**
     * Bloque / débloque un client (permission dédiée).
     */
    public function toggleBlock(Customer $customer): CustomerResource
    {
        $customer->update(['is_blocked' => ! $customer->is_blocked]);

        return CustomerResource::make($customer->refresh());
    }
}
