<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Settings\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PaymentMethodController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection(
            PaymentMethod::query()->orderBy('position')->orderBy('name')->get(),
        );
    }

    public function store(SavePaymentMethodRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $method = PaymentMethod::query()->create($data);

        return PaymentMethodResource::make($method)->response()->setStatusCode(201);
    }

    public function update(SavePaymentMethodRequest $request, PaymentMethod $paymentMethod): PaymentMethodResource
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $paymentMethod->update($data);

        return PaymentMethodResource::make($paymentMethod->refresh());
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return response()->json(['message' => 'Mode de paiement supprimé.']);
    }
}
