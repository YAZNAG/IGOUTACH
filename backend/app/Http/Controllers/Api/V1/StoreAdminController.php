<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Store\Models\StoreOrder;
use App\Domain\Store\Models\StorePayment;
use App\Domain\Store\Models\StoreSetting;
use App\Domain\Store\Services\StoreCatalog;
use App\Domain\Store\Services\StoreOrders;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Tableau de bord de la boutique : commandes, règlements, articles en
 * ligne, réglages. Réservé à la permission « store.manage ».
 */
final class StoreAdminController extends Controller
{
    public function __construct(
        private readonly StoreOrders $commandes,
        private readonly StoreCatalog $catalogue,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => [
            ...$this->commandes->tableauDeBord(),
            'recent_orders' => StoreOrder::query()->with(['warehouse:id,code', 'sale:id,reference'])->withCount('lines')
                ->orderByDesc('id')->limit(8)->get()
                ->map(fn (StoreOrder $c): array => $this->commandes->serialiser($c))->values()->all(),
        ]]);
    }

    public function orders(Request $request): JsonResponse
    {
        $page = StoreOrder::query()
            ->with(['warehouse:id,code', 'sale:id,reference'])
            ->withCount('lines')
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->string('payment_status')->isNotEmpty(), fn ($q) => $q->where('payment_status', $request->string('payment_status')->value()))
            ->when($request->string('q')->isNotEmpty(), function ($q) use ($request): void {
                $t = $request->string('q')->value();
                $q->where(fn ($w) => $w->where('number', 'like', "%{$t}%")->orWhere('customer_name', 'like', "%{$t}%")->orWhere('customer_phone', 'like', "%{$t}%")->orWhere('city', 'like', "%{$t}%"));
            })
            ->when($request->string('date_from')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')->value()))
            ->orderByDesc('id')
            ->paginate(in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20);

        return response()->json([
            'data' => array_map(fn (StoreOrder $c): array => $this->commandes->serialiser($c), $page->items()),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }

    public function order(StoreOrder $order): JsonResponse
    {
        $order->load(['lines', 'payments.receiver:id,name', 'warehouse:id,code', 'sale:id,reference', 'handler:id,name']);

        return response()->json(['data' => $this->commandes->serialiser($order, true)]);
    }

    public function updateStatus(Request $request, StoreOrder $order): JsonResponse
    {
        /** @var array{status: string, warehouse_id?: int|null, reason?: string|null} $data */
        $data = $request->validate([
            'status' => ['required', Rule::in(StoreOrder::STATUSES)],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->commandes->changerStatut($order, $data['status'], $request->user()?->id, [
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->order($order->refresh());
    }

    public function addPayment(Request $request, StoreOrder $order): JsonResponse
    {
        /** @var array{method: string, amount: float, reference?: string|null, received_at: string, note?: string|null} $data */
        $data = $request->validate([
            'method' => ['required', Rule::in(['cod', 'transfer', 'cash', 'card', 'other'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:120'],
            'received_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->commandes->encaisser($order, $data['method'], (float) $data['amount'], $data['reference'] ?? null, $data['received_at'], $data['note'] ?? null, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->order($order->refresh());
    }

    public function deletePayment(StoreOrder $order, StorePayment $payment): JsonResponse
    {
        abort_if($payment->order_id !== $order->id, 404);
        $this->commandes->annulerPaiement($order, $payment);

        return $this->order($order->refresh());
    }

    /**
     * Les articles vus par la boutique, avec leur stock par lieu : pour
     * décider ce qui se vend en ligne et d'où on expédie.
     */
    public function products(Request $request): JsonResponse
    {
        $page = Product::query()
            ->with(['category:id,name', 'images'])
            ->where('is_active', true)
            ->when($request->string('q')->isNotEmpty(), function ($q) use ($request): void {
                foreach (preg_split('/\s+/u', trim($request->string('q')->value())) ?: [] as $mot) {
                    $q->where(fn ($w) => $w->where('name', 'like', "%{$mot}%")->orWhere('sku', 'like', "%{$mot}%"));
                }
            })
            ->when($request->has('visible'), fn ($q) => $q->where('store_visible', $request->boolean('visible')))
            ->when($request->boolean('without_image'), fn ($q) => $q->whereDoesntHave('images'))
            ->select('products.*')
            ->selectRaw("COALESCE((SELECT pp.amount FROM product_prices pp JOIN price_types pt ON pt.id = pp.price_type_id WHERE pp.product_id = products.id AND pt.code = 'detail' AND pp.valid_to IS NULL LIMIT 1), products.sale_price, 0) as store_price")
            ->selectRaw('(SELECT COALESCE(SUM(s.quantity), 0) FROM stocks s WHERE s.product_id = products.id) as stock_total')
            ->orderBy('name')
            ->paginate(in_array($request->integer('per_page', 30), [30, 60, 100], true) ? $request->integer('per_page', 30) : 30);

        $ids = collect($page->items())->pluck('id')->all();
        $parLieu = DB::table('stocks')->join('warehouses', 'warehouses.id', '=', 'stocks.warehouse_id')
            ->whereIn('stocks.product_id', $ids)->where('stocks.quantity', '>', 0)
            ->get(['stocks.product_id', 'warehouses.code', 'stocks.quantity'])
            ->groupBy('product_id');

        return response()->json([
            'data' => array_map(fn (Product $p): array => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name,
                'price' => round((float) $p->getAttribute('store_price'), 2),
                'stock_total' => (int) $p->getAttribute('stock_total'),
                'stock_by_warehouse' => ($parLieu[$p->id] ?? collect())->map(fn ($s) => ['warehouse' => $s->code, 'quantity' => (int) $s->quantity])->values()->all(),
                'store_visible' => (bool) $p->store_visible,
                'store_featured' => (bool) $p->store_featured,
                'store_description' => $p->store_description,
                'image' => $p->images->first() ? Storage::disk('public')->url($p->images->sortByDesc('is_main')->first()->path) : null,
                'images_count' => $p->images->count(),
            ], $page->items()),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }

    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'store_visible' => ['sometimes', 'boolean'],
            'store_featured' => ['sometimes', 'boolean'],
            'store_description' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);

        $product->update($data);

        return response()->json(['data' => [
            'id' => $product->id,
            'store_visible' => (bool) $product->store_visible,
            'store_featured' => (bool) $product->store_featured,
            'store_description' => $product->store_description,
        ]]);
    }

    public function settings(): JsonResponse
    {
        return response()->json(['data' => StoreSetting::tous()]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $regles = [];
        foreach (StoreSetting::CLES as $cle) {
            $regles[$cle] = ['sometimes', 'nullable', 'string', 'max:2000'];
        }
        $data = $request->validate($regles);

        foreach ($data as $cle => $valeur) {
            StoreSetting::query()->updateOrCreate(['key' => $cle], ['value' => $valeur]);
        }

        return response()->json(['data' => StoreSetting::tous()]);
    }
}
