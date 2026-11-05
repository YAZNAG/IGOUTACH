<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetCreditLimitRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Rules\WarehouseAccessible;
use App\Support\Query\Sortable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Domain\Sales\Models\Payment;
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
     * Prochain code CL-0001, CL-0002… apres le dernier attribue.
     */
    private function prochainCode(): string
    {
        $last = Customer::withTrashed()->where('code', 'like', 'CL-%')->orderByDesc('id')->value('code');
        $next = $last !== null ? ((int) substr((string) $last, 3)) + 1 : 1;

        return 'CL-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * La fiche du meme nom, si elle existe deja dans ce lieu.
     *
     * Le fichier est separe par lieu : un meme acheteur a une fiche par lieu
     * ou il achete. Mais DANS un lieu, deux fiches au meme nom ne sont qu'un
     * doublon, avec deux dettes a reconcilier a la main.
     */
    private function homonymeDansLeLieu(string $nom, ?int $warehouseId, ?int $saufId = null): ?Customer
    {
        if ($warehouseId === null) {
            return null;
        }

        return Customer::query()
            ->where('warehouse_id', $warehouseId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($nom))])
            ->when($saufId !== null, fn ($q) => $q->where('id', '!=', $saufId))
            ->first();
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // Le fichier client est separe par lieu : chaque fiche appartient a
        // un lieu, avec son credit, ses ventes et ses reglements. Un meme
        // acheteur present dans deux lieux y a deux fiches, independantes.
        // Un responsable ne voit que les fiches de son lieu ; la direction
        // voit tout et peut restreindre a un lieu.
        $lieu = $this->lieuDeLUtilisateur($request);

        $query = Customer::query()
            ->with('warehouse:id,code,name')
            ->when($lieu > 0, fn ($q) => $q->where('warehouse_id', $lieu))
            ->when($lieu === 0 && $request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
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

        return CustomerResource::collection($query->paginate($this->perPage($request)));
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
            $data['code'] = $this->prochainCode();
        }

        // Le lieu de la fiche : celui du responsable, sans discussion. La
        // direction, qui n'a pas de lieu, doit le choisir : une fiche sans
        // lieu n'apparaitrait chez aucun responsable.
        $lieu = $this->lieuDeLUtilisateur($request);
        if ($lieu > 0) {
            $data['warehouse_id'] = $lieu;
        } elseif (empty($data['warehouse_id'])) {
            // Direction rattachee a un lieu (vue globale, mais un lieu sur sa
            // fiche) : ce lieu est le sien par defaut, sans avoir a le dire.
            $data['warehouse_id'] = $request->user()?->getAttribute('warehouse_id');
        }

        if (empty($data['warehouse_id'])) {
            return response()->json([
                'message' => 'Choisissez le lieu du client : chaque fiche client appartient à un lieu.',
                'errors' => ['warehouse_id' => ['Le lieu est obligatoire.']],
            ], 422);
        }

        $double = $this->homonymeDansLeLieu($data['name'], (int) $data['warehouse_id']);
        if ($double !== null) {
            return response()->json([
                'message' => sprintf('Ce client existe déjà dans ce lieu sous le code %s.', $double->code),
                'errors' => ['name' => ['Client déjà présent dans ce lieu.']],
                'existing_id' => $double->id,
            ], 422);
        }

        $data['created_by'] = $request->user()?->id;

        $customer = Customer::query()->create($data);

        return CustomerResource::make($customer->load('warehouse:id,code,name'))->response()->setStatusCode(201);
    }

    /**
     * La meme personne, cliente dans un autre lieu : une seconde fiche.
     *
     * POST /customers/{customer}/copy  { warehouse_id }
     *
     * L'identite est recopiee (nom, telephone, adresse, ICE…), rien d'autre :
     * le credit repart de zero, les ventes et reglements de l'autre lieu
     * restent la-bas. C'est le principe du fichier separe.
     */
    public function copierVersLieu(Request $request, Customer $customer): JsonResponse
    {
        $this->assertCanSee($request, $customer);

        /** @var array{warehouse_id: int} $data */
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
        ]);

        $cible = (int) $data['warehouse_id'];

        if ($cible === (int) $customer->warehouse_id) {
            return response()->json(['message' => 'Ce client est déjà rattaché à ce lieu.'], 422);
        }

        $double = $this->homonymeDansLeLieu($customer->name, $cible);
        if ($double !== null) {
            return response()->json([
                'message' => sprintf('%s a déjà une fiche dans ce lieu : %s.', $customer->name, $double->code),
                'existing_id' => $double->id,
            ], 422);
        }

        $copie = Customer::query()->create([
            'code' => $this->prochainCode(),
            'name' => $customer->name,
            'is_company' => $customer->is_company,
            'contact_name' => $customer->contact_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'city' => $customer->city,
            'ice' => $customer->ice,
            'price_type_id' => $customer->price_type_id,
            'warehouse_id' => $cible,
            // Le plafond suit la fiche seulement pour qui a le droit d'en fixer un.
            'credit_limit' => ($request->user()?->can('customer.set_credit_limit') ?? false) ? $customer->credit_limit : 0,
            'balance' => 0,
            'is_blocked' => false,
            'notes' => $customer->notes,
            'is_active' => true,
            'created_by' => $request->user()?->id,
        ]);

        return CustomerResource::make($copie->load('warehouse:id,code,name'))->response()->setStatusCode(201);
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->assertCanSee($request, $customer);

        return CustomerResource::make($customer->load(['priceType:id,name', 'seller:id,name', 'warehouse:id,code,name', 'createdBy:id,name']));
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
     * Une fiche appartient a un lieu : hors de la direction, on ne consulte,
     * ne modifie et n'encaisse que les clients de son propre lieu.
     */
    private function assertCanSee(Request $request, Customer $customer): void
    {
        $lieu = $this->lieuDeLUtilisateur($request);

        if ($lieu > 0 && (int) $customer->warehouse_id !== $lieu) {
            abort(403, 'Ce client appartient à un autre lieu.');
        }
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource|JsonResponse
    {
        $this->assertCanSee($request, $customer);

        $data = $request->validated();

        // Le lieu d'une fiche ne se change pas en passant : un responsable
        // n'y touche pas, et la direction ne deplace que des fiches sans
        // historique — sinon ses ventes resteraient dans l'ancien lieu.
        $lieu = $this->lieuDeLUtilisateur($request);
        if ($lieu > 0) {
            unset($data['warehouse_id']);
        } elseif (array_key_exists('warehouse_id', $data)
            && (int) $data['warehouse_id'] !== (int) $customer->warehouse_id
            && (Sale::query()->where('customer_id', $customer->id)->exists()
                || Payment::query()->where('customer_id', $customer->id)->exists())) {
            return response()->json([
                'message' => 'Ce client a déjà des ventes ou des règlements dans son lieu : créez plutôt sa fiche dans l’autre lieu.',
                'errors' => ['warehouse_id' => ['Lieu non modifiable.']],
            ], 422);
        }

        $cibleLieu = array_key_exists('warehouse_id', $data)
            ? ($data['warehouse_id'] !== null ? (int) $data['warehouse_id'] : null)
            : $customer->warehouse_id;
        $double = $this->homonymeDansLeLieu($data['name'], $cibleLieu, $customer->id);
        if ($double !== null) {
            return response()->json([
                'message' => sprintf('Un autre client porte déjà ce nom dans ce lieu : %s.', $double->code),
                'errors' => ['name' => ['Client déjà présent dans ce lieu.']],
            ], 422);
        }

        $customer->update($data);

        return CustomerResource::make($customer->refresh()->load('warehouse:id,code,name'));
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->assertCanSee($request, $customer);

        $customer->delete();

        return response()->json(['message' => 'Client supprimé.']);
    }

    /**
     * Définit le plafond de crédit (permission dédiée).
     */
    public function setCreditLimit(SetCreditLimitRequest $request, Customer $customer): CustomerResource
    {
        $this->assertCanSee($request, $customer);

        /** @var array{credit_limit: int|float} $data */
        $data = $request->validated();
        $customer->update(['credit_limit' => $data['credit_limit']]);

        return CustomerResource::make($customer->refresh());
    }

    /**
     * Bloque / débloque un client (permission dédiée).
     */
    public function toggleBlock(Request $request, Customer $customer): CustomerResource
    {
        $this->assertCanSee($request, $customer);

        $customer->update(['is_blocked' => ! $customer->is_blocked]);

        return CustomerResource::make($customer->refresh());
    }
}
