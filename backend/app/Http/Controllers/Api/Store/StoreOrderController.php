<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Store;

use App\Domain\Store\Models\StoreOrder;
use App\Domain\Store\Services\StoreOrders;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Commandes passées depuis la boutique, et leur suivi par le client.
 */
final class StoreOrderController extends Controller
{
    public function __construct(private readonly StoreOrders $commandes) {}

    public function store(Request $request): JsonResponse
    {
        /** @var array{customer_name: string, customer_phone: string, customer_email?: string|null, city: string, address: string, note?: string|null, payment_method: string, lines: list<array{product_id: int, quantity: int}>} $data */
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'min:9', 'max:30', 'regex:/^[0-9+\s().-]+$/'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in([StoreOrder::PAYMENT_COD, StoreOrder::PAYMENT_TRANSFER])],
            'lines' => ['required', 'array', 'min:1', 'max:40'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        try {
            $commande = $this->commandes->passer($data, $request->ip());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->commandes->suivi($commande)], 201);
    }

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'min:9', 'max:30'],
        ]);

        $commande = $this->commandes->suivre($data['number'], $data['phone']);

        if ($commande === null) {
            return response()->json(['message' => 'Aucune commande ne correspond à ce numéro et ce téléphone.'], 404);
        }

        return response()->json(['data' => $this->commandes->suivi($commande)]);
    }
}
