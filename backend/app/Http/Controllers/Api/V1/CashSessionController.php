<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Models\CashSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Rules\WarehouseAccessible;

/**
 * Sessions de caisse : ouverture avec fonds, clôture avec calcul d'écart.
 */
final class CashSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = CashSession::query()
            ->with(['warehouse:id,code', 'opener:id,name'])
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->orderByDesc('id')
            ->paginate(20);

        $sessions->through(fn (CashSession $s): array => $this->serialize($s));

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
     * Session ouverte pour un lieu (au plus une à la fois).
     */
    public function current(Request $request): JsonResponse
    {
        $session = CashSession::query()
            ->with(['warehouse:id,code', 'opener:id,name'])
            ->where('warehouse_id', $request->integer('warehouse_id'))
            ->where('status', CashSession::STATUS_OPEN)
            ->latest('id')
            ->first();

        return response()->json(['data' => $session !== null ? $this->serialize($session) : null]);
    }

    public function open(Request $request): JsonResponse
    {
        /** @var array{warehouse_id: int, opening_amount: float} $data */
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

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
            'opening_amount' => $data['opening_amount'],
            'status' => CashSession::STATUS_OPEN,
        ]);

        return response()->json(['data' => $this->serialize($session->load(['warehouse:id,code', 'opener:id,name']))], 201);
    }

    public function close(Request $request, CashSession $cashSession): JsonResponse
    {
        /** @var array{closing_amount: float} $data */
        $data = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ]);

        if ($cashSession->status !== CashSession::STATUS_OPEN) {
            return response()->json(['message' => 'Session déjà clôturée.'], 422);
        }

        // Attendu = fonds d'ouverture + encaissements rattachés à la session.
        $collected = (float) DB::table('payments')
            ->where('cash_session_id', $cashSession->id)
            ->sum('amount');

        $expected = round((float) $cashSession->opening_amount + $collected, 2);
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
