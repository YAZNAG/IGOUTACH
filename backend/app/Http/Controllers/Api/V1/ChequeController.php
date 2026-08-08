<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\Models\Cheque;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Portefeuille de chèques : saisie, consultation, endossement.
 */
final class ChequeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cheque::query()->with(['customer:id,name', 'supplier:id,name']);

        if ($request->filled('direction')) {
            $query->where('direction', $request->string('direction')->value());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->integer('customer_id') > 0) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        // Chèques mobilisables pour payer un fournisseur : reçus et non encore
        // remis. Le filtre est explicite pour éviter qu'un écran propose par
        // mégarde un chèque déjà endossé.
        if ($request->boolean('endorsable')) {
            $query->where('direction', Cheque::DIRECTION_IN)
                ->where('status', Cheque::STATUS_PORTFOLIO);
        }

        if ($request->filled('search')) {
            $terme = '%'.$request->string('search')->value().'%';
            $query->where(fn ($q) => $q->where('number', 'like', $terme)->orWhere('drawer_name', 'like', $terme));
        }

        $cheques = $query->orderByDesc('cheque_date')->orderByDesc('id')->limit(200)->get();

        return response()->json(['data' => $cheques->map(fn (Cheque $c) => $this->format($c))->all()]);
    }

    public function show(Cheque $cheque): JsonResponse
    {
        return response()->json(['data' => $this->format($cheque->load(['customer:id,name', 'supplier:id,name']))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:50'],
            'cheque_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank' => ['nullable', 'string', 'max:100'],
            'direction' => ['required', Rule::in([Cheque::DIRECTION_IN, Cheque::DIRECTION_OUT])],
            'origin' => ['required', Rule::in([Cheque::ORIGIN_CUSTOMER, Cheque::ORIGIN_OWN, Cheque::ORIGIN_THIRD_PARTY])],
            'drawer_name' => ['nullable', 'string', 'max:191'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        // Un chèque signé par un tiers n'a d'intérêt que si l'on sait qui l'a
        // signé : sans ce nom, impossible de le réclamer en cas de rejet.
        if ($data['origin'] === Cheque::ORIGIN_THIRD_PARTY && blank($data['drawer_name'] ?? null)) {
            return response()->json([
                'message' => 'Le nom porté sur le chèque est obligatoire pour un chèque signé par un tiers.',
                'errors' => ['drawer_name' => ['Nom du signataire requis.']],
            ], 422);
        }

        if ($request->hasFile('image')) {
            /** @var UploadedFile $file */
            $file = $request->file('image');
            $data['image_path'] = (string) $file->store('cheques', 'public');
        }

        unset($data['image']);

        $data['status'] = Cheque::STATUS_PORTFOLIO;
        $data['created_by'] = $request->user()?->id;

        $cheque = Cheque::query()->create($data);

        return response()->json(['data' => $this->format($cheque->load(['customer:id,name', 'supplier:id,name']))], 201);
    }

    /**
     * Endosse un chèque reçu au profit d'un fournisseur.
     */
    public function endorse(Request $request, Cheque $cheque): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ]);

        // Verrou : deux endossements simultanés du même chèque conduiraient à
        // régler deux fournisseurs avec un seul chèque.
        return DB::transaction(function () use ($cheque, $data): JsonResponse {
            $verrouille = Cheque::query()->lockForUpdate()->find($cheque->id);

            if ($verrouille === null || ! $verrouille->estEndossable()) {
                return response()->json([
                    'message' => 'Ce chèque n’est plus disponible : il a déjà été remis ou encaissé.',
                ], 422);
            }

            $verrouille->update([
                'supplier_id' => $data['supplier_id'],
                'status' => Cheque::STATUS_HANDED_OVER,
            ]);

            return response()->json(['data' => $this->format($verrouille->load(['customer:id,name', 'supplier:id,name']))]);
        });
    }

    /**
     * Fait évoluer l'état bancaire : encaissé ou rejeté.
     */
    public function updateStatus(Request $request, Cheque $cheque): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Cheque::STATUS_PORTFOLIO,
                Cheque::STATUS_HANDED_OVER,
                Cheque::STATUS_CASHED,
                Cheque::STATUS_BOUNCED,
            ])],
        ]);

        $cheque->update(['status' => $data['status']]);

        return response()->json(['data' => $this->format($cheque->load(['customer:id,name', 'supplier:id,name']))]);
    }

    public function destroy(Cheque $cheque): JsonResponse
    {
        if ($cheque->status !== Cheque::STATUS_PORTFOLIO) {
            return response()->json([
                'message' => 'Seul un chèque encore en portefeuille peut être supprimé.',
            ], 422);
        }

        if ($cheque->image_path !== null) {
            Storage::disk('public')->delete($cheque->image_path);
        }

        $cheque->delete();

        return response()->json(['message' => 'Chèque supprimé.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Cheque $cheque): array
    {
        return [
            'id' => $cheque->id,
            'number' => $cheque->number,
            'cheque_date' => $cheque->cheque_date?->toDateString(),
            'amount' => (float) $cheque->amount,
            'bank' => $cheque->bank,
            'direction' => $cheque->direction,
            'origin' => $cheque->origin,
            'drawer_name' => $cheque->drawer_name,
            'signataire' => $cheque->signataire,
            'customer' => $cheque->customer?->only(['id', 'name']),
            'supplier' => $cheque->supplier?->only(['id', 'name']),
            'image_url' => $cheque->image_url,
            'status' => $cheque->status,
            'note' => $cheque->note,
            'created_at' => $cheque->created_at?->toDateTimeString(),
        ];
    }
}
