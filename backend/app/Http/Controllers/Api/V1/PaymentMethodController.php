<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Settings\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $usages = $this->compterUsages($paymentMethod->id);

        // Un mode déjà employé ne peut pas disparaître : les règlements
        // existants perdraient l'indication de leur moyen de paiement. On le
        // désactive — il sort des listes de saisie sans effacer l'historique.
        if ($usages > 0) {
            $paymentMethod->update(['is_active' => false]);

            return response()->json([
                'message' => "Ce mode est utilisé par {$usages} règlement(s) : il a été désactivé plutôt que supprimé.",
                'data' => PaymentMethodResource::make($paymentMethod->refresh()),
            ]);
        }

        $paymentMethod->delete();

        return response()->json(['message' => 'Mode de paiement supprimé.']);
    }

    /**
     * Nombre d'écritures rattachées à ce mode de paiement.
     */
    private function compterUsages(int $id): int
    {
        $total = 0;

        foreach (['payments', 'supplier_payments', 'expenses', 'recurring_expense_occurrences'] as $table) {
            if (Schema::hasColumn($table, 'payment_method_id')) {
                $total += DB::table($table)->where('payment_method_id', $id)->count();
            }
        }

        return $total;
    }
}
