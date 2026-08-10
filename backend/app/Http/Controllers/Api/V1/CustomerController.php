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

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Customer::query()
            // Sans « customer.view_all », chacun ne voit que les clients qu'il a créés.
            ->when(! ($request->user()?->can('customer.view_all') ?? false), fn ($q) => $q->where('created_by', $request->user()?->id))
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

        // Les ventes sont deja cloisonnees par lieu et par vendeur : ce que
        // l'on voit ici est ce que l'on a le droit de voir ailleurs.
        $ventes = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('type', Sale::TYPE_INVOICE)
            ->orderByDesc('confirmed_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $reglements = DB::table('payments')
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
                'balance' => round((float) $customer->balance, 2),
                'limit' => round((float) $customer->credit_limit, 2),
                'is_blocked' => (bool) $customer->is_blocked,
                // Part du plafond consommee : null quand aucun plafond n'est
                // fixe, sinon on afficherait une jauge qui ne veut rien dire.
                'usage_percent' => (float) $customer->credit_limit > 0
                    ? round((float) $customer->balance / (float) $customer->credit_limit * 100, 1)
                    : null,
                'unpaid_count' => $confirmees->where('payment_status', '!=', 'paid')->count(),
            ],
            'stats' => [
                'sales_count' => $confirmees->count(),
                'total_purchased' => round($total, 2),
                'average_basket' => $confirmees->count() > 0 ? round($total / $confirmees->count(), 2) : 0.0,
                'last_purchase' => $confirmees->first()?->confirmed_at?->toDateString(),
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
     * Refuse l'accès à un client créé par un autre utilisateur (sauf view_all).
     */
    private function assertCanSee(Request $request, Customer $customer): void
    {
        $user = $request->user();
        if ($user !== null && ! $user->can('customer.view_all') && $customer->created_by !== null && $customer->created_by !== $user->id) {
            abort(403, 'Ce client a été créé par un autre utilisateur.');
        }
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
