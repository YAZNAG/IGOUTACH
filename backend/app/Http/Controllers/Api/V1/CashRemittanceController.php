<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Models\CashRemittance;
use App\Domain\Sales\Models\CashSession;
use App\Domain\Sales\Services\CashBoxService;
use App\Http\Controllers\Controller;
use App\Rules\WarehouseAccessible;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Remises de caisse : du responsable de lieu vers l'administration.
 *
 * Le geste est en deux temps parce que l'argent circule en deux temps : il
 * part du lieu, puis il arrive. Tant que la direction n'a pas confirmé, la
 * remise reste « en route » et les deux parties voient la même chose.
 */
final class CashRemittanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $remises = CashRemittance::query()
            ->with(['warehouse:id,code,name', 'creator:id,name', 'receiver:id,name'])
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('remitted_at', '>=', $request->string('date_from')->value()))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('remitted_at', '<=', $request->string('date_to')->value()))
            ->orderByDesc('id')
            ->paginate(30);

        $remises->through(fn (CashRemittance $r): array => $this->serialize($r));

        return response()->json([
            'data' => $remises->items(),
            'meta' => [
                'current_page' => $remises->currentPage(),
                'last_page' => $remises->lastPage(),
                'per_page' => $remises->perPage(),
                'total' => $remises->total(),
                // Ce que l'administration attend encore : le chiffre qui
                // ouvre la discussion entre elle et les lieux.
                'pending_total' => round((float) CashRemittance::query()
                    ->where('status', CashRemittance::STATUS_PENDING)
                    ->sum('amount'), 2),
            ],
        ]);
    }

    public function store(
        Request $request,
        CashBoxService $caisse,
        DocumentNumberGeneratorInterface $numeros,
    ): JsonResponse {
        /** @var array{warehouse_id: int, amount: float, remitted_at?: string|null, note?: string|null} $data */
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remitted_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            // Photo du reçu, des billets comptés ou du bordereau : ce que le
            // responsable montre pour dire « voici ce que j'ai remis ».
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $session = CashSession::withoutGlobalScopes()
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('status', CashSession::STATUS_OPEN)
            ->latest('id')
            ->first();

        $solde = $caisse->solde((int) $data['warehouse_id'], $session);

        // Remettre plus que ce que contient le tiroir ne décrit aucune réalité :
        // ou bien le fonds de départ est faux, ou bien une entrée manque. Le
        // dire tout de suite évite un écart qu'on cherchera plus tard.
        if ((float) $data['amount'] > $solde['expected'] + 0.001) {
            return response()->json([
                'message' => sprintf(
                    'La caisse ne contient que %s DH : impossible d’en remettre %s DH.',
                    number_format($solde['expected'], 2, ',', ' '),
                    number_format((float) $data['amount'], 2, ',', ' '),
                ),
                'cash' => $solde,
            ], 422);
        }

        $preuve = $request->hasFile('proof')
            ? (string) $request->file('proof')->store('cash-remittances', 'public')
            : null;

        $remise = CashRemittance::query()->create([
            'reference' => $numeros->next('cash_remittance'),
            'proof_path' => $preuve,
            'warehouse_id' => $data['warehouse_id'],
            'cash_session_id' => $session?->id,
            'amount' => $data['amount'],
            'remitted_at' => $data['remitted_at'] ?? now()->toDateString(),
            'status' => CashRemittance::STATUS_PENDING,
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => $this->serialize($remise->load(['warehouse:id,code,name', 'creator:id,name'])),
            'cash' => $caisse->solde((int) $data['warehouse_id'], $session),
        ], 201);
    }

    /**
     * L'administration confirme avoir reçu la somme.
     */
    public function receive(Request $request, CashRemittance $cashRemittance): JsonResponse
    {
        if ($cashRemittance->status !== CashRemittance::STATUS_PENDING) {
            return response()->json(['message' => 'Cette remise est déjà confirmée.'], 422);
        }

        $cashRemittance->update([
            'status' => CashRemittance::STATUS_RECEIVED,
            'received_by' => $request->user()?->id,
            'received_at' => now(),
        ]);

        return response()->json([
            'data' => $this->serialize(
                $cashRemittance->refresh()->load(['warehouse:id,code,name', 'creator:id,name', 'receiver:id,name']),
            ),
        ]);
    }

    /**
     * L'administration refuse : elle n'a pas reçu cette somme.
     *
     * Le montant revient au solde du lieu et la ligne reste dans l'historique
     * avec son motif. Effacer le refus reviendrait à effacer le désaccord.
     */
    public function refuse(Request $request, CashRemittance $cashRemittance): JsonResponse
    {
        if ($cashRemittance->status !== CashRemittance::STATUS_PENDING) {
            return response()->json([
                'message' => 'Seul un transfert en attente peut être refusé.',
            ], 422);
        }

        /** @var array{reason?: string|null} $data */
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $cashRemittance->update([
            'status' => CashRemittance::STATUS_REFUSED,
            'refused_by' => $request->user()?->id,
            'refused_at' => now(),
            'refusal_reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'data' => $this->serialize(
                $cashRemittance->refresh()->load(['warehouse:id,code,name', 'creator:id,name', 'refuser:id,name']),
            ),
        ]);
    }

    /**
     * Annule une remise déclarée par erreur, tant qu'elle n'est pas confirmée.
     *
     * Une remise confirmée ne s'efface pas : la direction a compté l'argent,
     * le supprimer ferait mentir son propre décompte.
     */
    public function destroy(CashRemittance $cashRemittance): JsonResponse
    {
        if ($cashRemittance->status !== CashRemittance::STATUS_PENDING) {
            return response()->json([
                'message' => 'Cette remise a été confirmée par l’administration : elle ne peut plus être supprimée.',
            ], 422);
        }

        $cashRemittance->delete();

        return response()->json(['message' => 'Remise annulée.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(CashRemittance $r): array
    {
        return [
            'id' => $r->id,
            'reference' => $r->reference,
            'warehouse' => $r->warehouse?->code,
            'warehouse_name' => $r->warehouse?->name,
            'amount' => (float) $r->amount,
            'remitted_at' => $r->remitted_at?->format('Y-m-d'),
            'created_at' => $r->created_at?->format('Y-m-d H:i'),
            'status' => $r->status,
            'note' => $r->note,
            'created_by' => $r->creator?->name,
            'received_by' => $r->receiver?->name,
            'received_at' => $r->received_at?->format('Y-m-d H:i'),
            // Le justificatif est servi comme celui des charges, depuis le
            // disque public : l'administration le regarde avant de confirmer.
            'proof_url' => $r->proof_path !== null ? url('storage/'.$r->proof_path) : null,
            'refused_by' => $r->refuser?->name,
            'refused_at' => $r->refused_at?->format('Y-m-d H:i'),
            'refusal_reason' => $r->refusal_reason,
        ];
    }
}
