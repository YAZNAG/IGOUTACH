<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImageProposal;
use App\Domain\Catalog\Services\AgentImages;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Page « Agent images » : l'agent cherche des photos pour les articles sans
 * visuel, l'administrateur valide ou rejette, seule l'image validée entre
 * dans la base.
 */
final class AgentImagesController extends Controller
{
    public function overview(AgentImages $agent): JsonResponse
    {
        return response()->json(['data' => [
            'configured' => $agent->configure(),
            'counts' => $agent->compteurs(),
            'max_per_run' => AgentImages::MAX_PAR_LANCEMENT,
            // Les prochains articles que l'agent traiterait, pour choisir.
            'next_products' => $agent->sansImage()->limit(60)->get(['id', 'sku', 'name'])
                ->map(fn (Product $p): array => ['id' => $p->id, 'sku' => $p->sku, 'name' => $p->name])->values()->all(),
        ]]);
    }

    public function index(Request $request, AgentImages $agent): JsonResponse
    {
        $statut = $request->string('status')->value() ?: ProductImageProposal::STATUS_PENDING;

        $page = ProductImageProposal::query()
            ->with(['product:id,sku,name,category_id,brand_id', 'product.category:id,name', 'product.brand:id,name', 'image:id,path', 'reviewer:id,name'])
            ->when(in_array($statut, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $statut))
            ->when($request->string('q')->isNotEmpty(), function ($q) use ($request): void {
                $terme = $request->string('q')->value();
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$terme}%")->orWhere('sku', 'like', "%{$terme}%"));
            })
            ->orderByDesc('updated_at')
            ->paginate(30);

        return response()->json([
            'data' => $agent->serialiser(collect($page->items())),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Lance l'agent : sur les articles choisis, ou les premiers sans image.
     */
    public function run(Request $request, AgentImages $agent): JsonResponse
    {
        /** @var array{limit?: int, product_ids?: list<int>} $data */
        $data = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.AgentImages::MAX_PAR_LANCEMENT],
            'product_ids' => ['sometimes', 'array', 'max:'.AgentImages::MAX_PAR_LANCEMENT],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        // Vingt-cinq recherches peuvent prendre une minute.
        set_time_limit(300);

        try {
            $resultat = $agent->lancer(
                (int) ($data['limit'] ?? 10),
                isset($data['product_ids']) ? array_map('intval', $data['product_ids']) : null,
                $request->user()?->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json(['data' => $resultat]);
    }

    /**
     * Relance la recherche d'un article avec une requête corrigée à la main.
     */
    public function retry(Request $request, ProductImageProposal $proposal, AgentImages $agent): JsonResponse
    {
        /** @var array{query: string} $data */
        $data = $request->validate(['query' => ['required', 'string', 'min:2', 'max:200']]);

        try {
            $nouvelle = $agent->proposer($proposal->product()->firstOrFail(), $request->user()?->id, $data['query']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        if ($nouvelle === null) {
            return response()->json(['message' => 'Aucune image trouvée pour « '.$data['query'].' ».'], 422);
        }

        $nouvelle->load(['product:id,sku,name,category_id,brand_id', 'product.category:id,name', 'product.brand:id,name']);

        return response()->json(['data' => $agent->serialiser(collect([$nouvelle]))[0]]);
    }

    public function approve(Request $request, ProductImageProposal $proposal, AgentImages $agent): JsonResponse
    {
        /** @var array{index: int} $data */
        $data = $request->validate(['index' => ['required', 'integer', 'min:0', 'max:'.(AgentImages::CANDIDATES - 1)]]);

        try {
            $image = $agent->approuver($proposal, (int) $data['index'], $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => [
            'proposal_id' => $proposal->id,
            'image' => ['id' => $image->id, 'url' => Storage::disk('public')->url($image->path), 'is_main' => $image->is_main],
        ]]);
    }

    public function reject(Request $request, ProductImageProposal $proposal, AgentImages $agent): JsonResponse
    {
        try {
            $agent->rejeter($proposal, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Proposition rejetée.']);
    }

    /**
     * Efface une proposition traitée de l'historique (l'image validée reste).
     */
    public function destroy(ProductImageProposal $proposal): JsonResponse
    {
        abort_if($proposal->status === ProductImageProposal::STATUS_PENDING, 422, 'Validez ou rejetez d’abord cette proposition.');
        $proposal->delete();

        return response()->json(['message' => 'Proposition supprimée.']);
    }

    /**
     * Les statuts acceptés par le filtre de la liste.
     *
     * @return list<string>
     */
    public static function statuts(): array
    {
        return [ProductImageProposal::STATUS_PENDING, ProductImageProposal::STATUS_APPROVED, ProductImageProposal::STATUS_REJECTED];
    }
}
