<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Models\CashSession;
use App\Domain\Sales\Services\CashBoxService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\WarehouseAccessible;

/**
 * Sessions de caisse : ouverture avec fonds, clôture avec calcul d'écart.
 */
final class CashSessionController extends Controller
{
    public function index(Request $request, CashBoxService $caisse): JsonResponse
    {
        $sessions = CashSession::query()
            ->with(['warehouse:id,code', 'opener:id,name'])
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            // On cherche une session par le lieu ou par qui l'a ouverte : ce
            // sont les deux seules prises qu'offre une ligne de caisse.
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request): void {
                $terme = '%'.$request->string('search')->value().'%';
                $q->where(function ($x) use ($terme): void {
                    $x->whereHas('warehouse', fn ($w) => $w->where('code', 'like', $terme)->orWhere('name', 'like', $terme))
                        ->orWhereHas('opener', fn ($u) => $u->where('name', 'like', $terme));
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        // Chaque journee close est detaillee : entrees, charges, remises. Le
        // seul « attendu » ne dit pas d'ou vient l'argent, et c'est cela qu'on
        // vient chercher en relisant une journee passee.
        $sessions->through(fn (CashSession $s): array => $this->serialize($s) + $this->mouvements($s, $caisse));

        return response()->json([
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    /**
     * L'état des caisses de tous les lieux, en une vue.
     *
     * La direction n'a pas de tiroir à elle : ce qu'elle veut savoir, c'est
     * combien chaque lieu détient, depuis quand la journée est ouverte, et ce
     * qui lui a été annoncé sans qu'elle l'ait encore confirmé.
     */
    public function overview(Request $request, CashBoxService $caisse): JsonResponse
    {
        $lieux = \App\Domain\Warehouses\Models\Warehouse::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $enAttente = \App\Domain\Sales\Models\CashRemittance::withoutGlobalScopes()
            ->where('status', \App\Domain\Sales\Models\CashRemittance::STATUS_PENDING)
            ->selectRaw('warehouse_id, COUNT(*) as nb, COALESCE(SUM(amount),0) as total')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        $data = $lieux->map(function ($w) use ($caisse, $enAttente): array {
            $session = CashSession::withoutGlobalScopes()
                ->where('warehouse_id', $w->id)
                ->where('status', CashSession::STATUS_OPEN)
                ->latest('id')
                ->first();

            $solde = $caisse->solde((int) $w->id, $session);
            $attente = $enAttente->get($w->id);

            return [
                'warehouse_id' => $w->id,
                'code' => $w->code,
                'name' => $w->name,
                'session_open' => $session !== null,
                'opened_at' => $session?->opened_at?->format('Y-m-d H:i'),
                ...$solde,
                'pending_count' => (int) ($attente->nb ?? 0),
                'pending_total' => round((float) ($attente->total ?? 0), 2),
                'last_closed' => $this->derniereCloturee((int) $w->id),
            ];
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total_expected' => round(array_sum(array_column($data, 'expected')), 2),
                'total_pending' => round(array_sum(array_column($data, 'pending_total')), 2),
            ],
        ]);
    }

    /**
     * Session ouverte pour un lieu (au plus une à la fois).
     */
    public function current(Request $request, CashBoxService $caisse): JsonResponse
    {
        $lieu = $request->integer('warehouse_id');

        $session = CashSession::query()
            ->with(['warehouse:id,code', 'opener:id,name'])
            ->where('warehouse_id', $lieu)
            ->where('status', CashSession::STATUS_OPEN)
            ->latest('id')
            ->first();

        // Le solde accompagne toujours la session, meme absente : sans caisse
        // ouverte, le responsable voit quand meme ce qui est passe par ses
        // mains aujourd'hui.
        return response()->json([
            'data' => $session !== null ? $this->serialize($session) : null,
            'cash' => $lieu > 0 ? $caisse->solde($lieu, $session) : null,
            // Le fonds du lendemain, c'est le reste de la veille : la journee
            // suivante s'ouvre sur ce chiffre, sans ressaisie et sans rupture
            // dans la suite des journees.
            'suggested_opening' => $lieu > 0 ? $this->resteDeLaVeille($lieu) : null,
            'last_closed' => $lieu > 0 ? $this->derniereCloturee($lieu) : null,
        ]);
    }

    /**
     * Ce que la derniere journee close a laisse dans le tiroir.
     *
     * Le montant compte fait foi, pas l'attendu : c'est l'argent reellement
     * present qui se retrouvera dans le tiroir au matin.
     */
    private function resteDeLaVeille(int $lieu): float
    {
        $derniere = CashSession::query()
            ->where('warehouse_id', $lieu)
            ->where('status', CashSession::STATUS_CLOSED)
            ->latest('id')
            ->first();

        return round((float) ($derniere?->closing_amount ?? $derniere?->expected_amount ?? 0), 2);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function derniereCloturee(int $lieu): ?array
    {
        $derniere = CashSession::query()
            ->where('warehouse_id', $lieu)
            ->where('status', CashSession::STATUS_CLOSED)
            ->latest('id')
            ->first();

        return $derniere === null ? null : [
            'closed_at' => $derniere->closed_at?->format('Y-m-d H:i'),
            'closing_amount' => (float) ($derniere->closing_amount ?? 0),
        ];
    }

    /**
     * Mouvements du tiroir sur la duree d'une session.
     *
     * @return array<string, mixed>
     */
    private function mouvements(CashSession $s, CashBoxService $caisse): array
    {
        if ($s->closed_at === null) {
            return [];
        }

        $detail = $caisse->soldePeriode((int) $s->warehouse_id, $s->opened_at, $s->closed_at);

        return [
            'cash_in' => $detail['cash_in'],
            'cash_expenses' => $detail['cash_expenses'],
            'remitted' => $detail['remitted'],
        ];
    }

    public function open(Request $request): JsonResponse
    {
        /** @var array{warehouse_id: int, opening_amount?: float|null} $data */
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            // Facultatif : sans montant, la journee s'ouvre sur le reste de la
            // veille. Le responsable peut le corriger s'il a compte autre chose.
            'opening_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $fonds = $data['opening_amount'] ?? $this->resteDeLaVeille((int) $data['warehouse_id']);

        $alreadyOpen = CashSession::query()
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('status', CashSession::STATUS_OPEN)
            ->exists();

        if ($alreadyOpen) {
            return response()->json(['message' => 'Une session de caisse est déjà ouverte pour ce lieu.'], 422);
        }

        $session = CashSession::query()->create([
            'warehouse_id' => $data['warehouse_id'],
            'opened_by' => (int) $request->user()?->id,
            'opened_at' => now(),
            'opening_amount' => $fonds,
            'status' => CashSession::STATUS_OPEN,
        ]);

        return response()->json(['data' => $this->serialize($session->load(['warehouse:id,code', 'opener:id,name']))], 201);
    }

    public function close(Request $request, CashSession $cashSession, CashBoxService $caisse): JsonResponse
    {
        /** @var array{closing_amount: float} $data */
        $data = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ]);

        if ($cashSession->status !== CashSession::STATUS_OPEN) {
            return response()->json(['message' => 'Session déjà clôturée.'], 422);
        }

        // L'attendu tient compte des sorties : charges payées de la main à la
        // main et remises faites à l'administration. Ne compter que les
        // entrées produisait un écart permanent, inexplicable.
        $solde = $caisse->solde((int) $cashSession->warehouse_id, $cashSession);

        $expected = $solde['expected'];
        $difference = round((float) $data['closing_amount'] - $expected, 2);

        $cashSession->update([
            'closed_at' => now(),
            'closing_amount' => $data['closing_amount'],
            'expected_amount' => $expected,
            'difference' => $difference,
            'status' => CashSession::STATUS_CLOSED,
        ]);

        return response()->json(['data' => $this->serialize($cashSession->refresh()->load(['warehouse:id,code', 'opener:id,name']))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(CashSession $s): array
    {
        return [
            'id' => $s->id,
            'warehouse' => $s->warehouse?->code,
            'opened_by' => $s->opener?->name,
            'opened_at' => $s->opened_at->format('Y-m-d H:i'),
            'opening_amount' => (float) $s->opening_amount,
            'closed_at' => $s->closed_at?->format('Y-m-d H:i'),
            'closing_amount' => $s->closing_amount !== null ? (float) $s->closing_amount : null,
            'expected_amount' => $s->expected_amount !== null ? (float) $s->expected_amount : null,
            'difference' => $s->difference !== null ? (float) $s->difference : null,
            'status' => $s->status,
        ];
    }
}
