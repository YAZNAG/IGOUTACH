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
