<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lien article ↔ fournisseur : référence fournisseur, dernier prix, délai.
 * Fournit aussi les statistiques simples du fournisseur.
 */
final class SupplierProductController extends Controller
{
    public function index(Supplier $supplier): JsonResponse
    {
        return response()->json(['data' => $this->list($supplier)]);
    }

    public function attach(Request $request, Supplier $supplier, Product $product): JsonResponse
    {
        /** @var array{supplier_reference?: string|null, last_price?: float|null, lead_time_days?: int|null} $data */
        $data = $request->validate([
            'supplier_reference' => ['nullable', 'string', 'max:120'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $supplier->products()->syncWithoutDetaching([$product->id => [
            'supplier_reference' => $data['supplier_reference'] ?? null,
            'last_price' => $data['last_price'] ?? null,
            'lead_time_days' => $data['lead_time_days'] ?? null,
        ]]);

        return response()->json(['data' => $this->list($supplier)], 201);
    }

    public function detach(Supplier $supplier, Product $product): JsonResponse
    {
        $supplier->products()->detach($product->id);

        return response()->json(['data' => $this->list($supplier)]);
    }

    /**
     * Statistiques simples : articles référencés, contacts, délai moyen.
     */
    public function stats(Supplier $supplier): JsonResponse
    {
        $leadAvg = DB::table('product_supplier')
            ->where('supplier_id', $supplier->id)
            ->whereNotNull('lead_time_days')
            ->avg('lead_time_days');

        return response()->json(['data' => [
            'products_count' => $supplier->products()->count(),
            'contacts_count' => $supplier->contacts()->count(),
            'average_lead_time_days' => $leadAvg !== null ? round((float) $leadAvg, 1) : null,
        ]]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function list(Supplier $supplier): array
    {
        return $supplier->products()->orderBy('name')->get()->map(fn (Product $p): array => [
            'product_id' => $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
            /* @phpstan-ignore-next-line propriété pivot dynamique */
            'supplier_reference' => $p->pivot->supplier_reference,
            /* @phpstan-ignore-next-line propriété pivot dynamique */
            'last_price' => $p->pivot->last_price !== null ? (float) $p->pivot->last_price : null,
            /* @phpstan-ignore-next-line propriété pivot dynamique */
            'lead_time_days' => $p->pivot->lead_time_days,
        ])->values()->all();
    }
}
