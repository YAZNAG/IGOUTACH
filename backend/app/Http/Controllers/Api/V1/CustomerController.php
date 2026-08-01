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
        $customer = Customer::query()->create($request->validated());

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return CustomerResource::make($customer);
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
