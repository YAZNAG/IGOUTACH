<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Store;

use App\Domain\Store\Models\StoreQuote;
use App\Domain\Store\Services\StoreQuotes;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Devis demandés depuis la boutique, et réponse du client.
 *
 * Le lien reçu vaut authentification : personne d'autre ne connaît le jeton.
 * Rien n'y est modifiable à part la réponse, et la réponse ne se donne
 * qu'une fois.
 */
final class StoreQuoteController extends Controller
{
    public function __construct(private readonly StoreQuotes $devis) {}

    public function store(Request $request): JsonResponse
    {
        /** @var array{customer_name: string, customer_phone: string, customer_email?: string|null, company?: string|null, city: string, address?: string|null, note?: string|null, lines: list<array{product_id: int, quantity: int}>} $data */
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'min:9', 'max:30', 'regex:/^[0-9+\s().-]+$/'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'company' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:60'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        try {
            $devis = $this->devis->demander($data, $request->ip());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Le jeton part avec la réponse : c'est le lien de suivi du client.
        return response()->json(['data' => [
            ...$this->devis->vuePublique($devis),
            'public_token' => $devis->public_token,
        ]], 201);
    }

    public function show(string $token): JsonResponse
    {
        $devis = $this->devis->parJeton($token);

        if ($devis === null) {
            return response()->json(['message' => 'Ce devis est introuvable.'], 404);
        }

        return response()->json(['data' => $this->devis->vuePublique($devis)]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:12'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $devis = $this->devis->parJeton($token);

        if ($devis === null) {
            return response()->json(['message' => 'Ce devis est introuvable.'], 404);
        }

        try {
            $commande = $this->devis->accepter($devis, $data['payment_method'], $data['address'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => [
            'order_number' => $commande->number,
            'quote' => $this->devis->vuePublique($devis->refresh()),
        ]]);
    }

    public function decline(Request $request, string $token): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $devis = $this->devis->parJeton($token);

        if ($devis === null) {
            return response()->json(['message' => 'Ce devis est introuvable.'], 404);
        }

        try {
            $this->devis->refuser($devis, $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->devis->vuePublique($devis->refresh())]);
    }

    /**
     * Suivi par numéro et téléphone, comme pour une commande.
     */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'min:9', 'max:30'],
        ]);

        $tel = preg_replace('/\s+/', '', $data['phone']) ?? $data['phone'];

        $devis = StoreQuote::query()
            ->where('number', strtoupper(trim($data['number'])))
            ->where('customer_phone', 'like', '%'.substr($tel, -9))
            ->first();

        if ($devis === null) {
            return response()->json(['message' => 'Aucun devis ne correspond à ce numéro et ce téléphone.'], 404);
        }

        return response()->json(['data' => [
            ...$this->devis->vuePublique($devis),
            'public_token' => $devis->public_token,
        ]]);
    }
}
