<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Pricing\Contracts\MarginCalculatorInterface;
use App\Domain\Pricing\Contracts\PriceResolverInterface;
use App\Domain\Pricing\Exceptions\NoPriceDefinedException;
use App\Domain\Pricing\Services\ProductCostResolver;
use App\Domain\Sales\Actions\CancelSaleAction;
use App\Domain\Sales\Actions\ConfirmSaleAction;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use App\Rules\WarehouseAccessible;
use App\Domain\Stock\Contracts\StockReaderInterface;

/**
 * Ventes : devis et factures. Prix résolus côté serveur (type de prix du
 * client puis paliers de quantité), contrôle du prix plancher et du crédit.
 */
final class SaleController extends Controller
{
    /**
     * Vue globale : voit les ventes de tous les vendeurs et de tous les lieux.
     */
    private function hasGlobalView(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && ($user->can('stock.view_global') || $user->can('customer.view_all'));
    }

    /**
     * Restreint aux ventes du vendeur : celles qu'il a créées, plus celles
     * rattachées à ses propres clients (reprise d'un dossier client).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Sale>  $query
     */
    private function scopeToSeller(Request $request, $query): void
    {
        $userId = $request->user()?->id;

        $query->where(function ($q) use ($userId): void {
            $q->where('user_id', $userId)
                ->orWhereIn(
                    'customer_id',
                    Customer::query()->select('id')->where('created_by', $userId),
                );
        });
    }

    /**
     * Refuse l'accès à une vente d'un autre vendeur (sauf vue globale).
     */
    private function assertCanSeeSale(Request $request, Sale $sale): void
    {
        if ($this->hasGlobalView($request)) {
            return;
        }

        $userId = $request->user()?->id;
        $ownsCustomer = $sale->customer_id !== null
            && Customer::query()->whereKey($sale->customer_id)->where('created_by', $userId)->exists();

        if ($sale->user_id !== $userId && ! $ownsCustomer) {
            abort(403, 'Cette vente a été enregistrée par un autre vendeur.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $sales = Sale::query()
            ->with(['customer:id,code,name', 'warehouse:id,code'])
            ->withCount('lines')
            // Cloisonnement vendeur : ses ventes + celles de ses clients.
            ->when(! $this->hasGlobalView($request), fn ($q) => $this->scopeToSeller($request, $q))
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')->value()))
            ->when($request->integer('customer_id') > 0, fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('reference', 'like', '%'.$request->string('search')->value().'%'))
            ->when($request->string('date_from')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')->value()))
            ->orderByDesc('id')
            ->paginate(in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20);

        // Devis déjà convertis en facture (pour les griser dans la sélection).
        $convertedQuoteIds = Sale::query()
            ->whereNotNull('quote_id')
            ->pluck('quote_id')
            ->all();

        $sales->through(fn (Sale $s): array => [
            'id' => $s->id,
            'reference' => $s->reference,
            'type' => $s->type,
            'status' => $s->status,
            'customer' => $s->customer?->name,
            'warehouse' => $s->warehouse?->code,
            'total' => (float) $s->total,
            'paid_amount' => (float) $s->paid_amount,
            'payment_status' => $s->payment_status,
            'lines_count' => (int) ($s->lines_count ?? 0),
            'quote_id' => $s->quote_id,
            'converted' => $s->type === Sale::TYPE_QUOTE && in_array($s->id, $convertedQuoteIds, true),
            'created_at' => $s->created_at?->format('Y-m-d H:i'),
        ]);

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Prix applicable pour un article / client / quantité (aide à la saisie).
     */
    public function price(Request $request, PriceResolverInterface $resolver, MarginCalculatorInterface $margins, ProductCostResolver $cost): JsonResponse
    {
        /** @var array{product_id: int, quantity: int, customer_id?: int|null} $data */
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        try {
            // Les paramètres de requête GET arrivent en chaînes : cast explicite.
            $resolved = $resolver->resolve(
                (int) $data['product_id'],
                (int) $data['quantity'],
                isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            );
        } catch (NoPriceDefinedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

    /** @var Product $product */
        $product = Product::query()->findOrFail((int) $data['product_id']);
        $floor = $margins->floorPrice($cost->unitCost($product), 0.0);

        return response()->json(['data' => [
            'unit_price' => $resolved->amount,
            'price_type_code' => $resolved->priceTypeCode,
            'reason' => $resolved->reason,
            'floor_price' => round($floor, 2),
        ]]);
    }

    /**
     * Lignes dont la quantite depasse le stock du lieu.
     *
     * Les quantites d'un meme article sont additionnees : deux lignes de 6
     * sur un stock de 10 doivent etre refusees, alors que chacune passe
     * isolement.
     *
     * @param  list<array{product_id: int, quantity: int}>  $lines
     * @return list<array{product_id: int, requested: int, available: int, message: string}>
     */
    private function lignesSansStock(int $warehouseId, array $lines, StockReaderInterface $stock): array
    {
        $demande = [];

        foreach ($lines as $line) {
            $id = (int) $line['product_id'];
            $demande[$id] = ($demande[$id] ?? 0) + (int) $line['quantity'];
        }

        $manquants = [];

        foreach ($demande as $productId => $quantite) {
            $disponible = $stock->quantityFor($warehouseId, $productId);

            if ($quantite > $disponible) {
                $nom = Product::query()->find($productId)?->name ?? ('article #'.$productId);

                $manquants[] = [
                    'product_id' => $productId,
                    'requested' => $quantite,
                    'available' => $disponible,
                    'message' => sprintf('%s — demande %d, disponible %d', $nom, $quantite, $disponible),
                ];
            }
        }

        return $manquants;
    }

    public function store(
        Request $request,
        PriceResolverInterface $resolver,
        MarginCalculatorInterface $margins,
        ProductCostResolver $cost,
        DocumentNumberGeneratorInterface $numbers,
    ): JsonResponse {
        /** @var array{type: string, customer_id: int, warehouse_id: int, discount_percent?: float, note?: string|null, lines: list<array{product_id: int, quantity: int, unit_price?: float|null}>} $data */
        $data = $request->validate([
            'type' => ['required', 'in:quote,invoice'],
            // Nullable : client de passage (vente comptoir sans fiche ni crédit).
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'discount_percent' => ['sometimes', 'numeric', 'between:0,100'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $discount = (float) ($data['discount_percent'] ?? 0);
        if ($discount > 10 && ! ($request->user()?->can('sale.discount_over_limit') ?? false)) {
            return response()->json(['message' => 'Remise supérieure à 10 % : autorisation requise.'], 422);
        }

        $canBelowFloor = $request->user()?->can('sale.sell_below_floor') ?? false;

        // Une facture sort du stock : refuser tout de suite ce qui ne pourra
        // pas etre livre. La confirmation le bloquerait de toute facon, mais
        // apres que le vendeur a saisi toute sa vente.
        // Un devis reste libre : il peut porter sur ce qu'on va commander.
        if ($data['type'] === Sale::TYPE_INVOICE) {
            $manquants = $this->lignesSansStock(
                (int) $data['warehouse_id'],
                $data['lines'],
                app(StockReaderInterface::class),
            );

            if ($manquants !== []) {
                return response()->json([
                    'message' => 'Stock insuffisant : '.implode(' ; ', array_column($manquants, 'message')),
                    'errors' => ['lines' => array_column($manquants, 'message')],
                    'insufficient' => $manquants,
                ], 422);
            }
        }

        try {
            $sale = DB::transaction(function () use ($data, $discount, $canBelowFloor, $resolver, $margins, $cost, $numbers, $request): Sale {
                $subtotal = 0.0;
                $prepared = [];

                foreach ($data['lines'] as $line) {
                    $priceTypeCode = null;

                    if (isset($line['unit_price'])) {
                        // Prix saisi par le vendeur : prioritaire, le niveau reste informatif.
                        $unitPrice = (float) $line['unit_price'];
                        try {
                            $priceTypeCode = $resolver->resolve($line['product_id'], $line['quantity'], $data['customer_id'] ?? null)->priceTypeCode;
                        } catch (NoPriceDefinedException) {
                            // Article sans tarif défini : le prix saisi fait foi.
                        }
                    } else {
                        $resolved = $resolver->resolve($line['product_id'], $line['quantity'], $data['customer_id'] ?? null);
                        $unitPrice = $resolved->amount;
                        $priceTypeCode = $resolved->priceTypeCode;
                    }

                    /** @var Product $product */
                    $product = Product::query()->findOrFail($line['product_id']);
                    $floor = $margins->floorPrice($cost->unitCost($product), 0.0);

                    if ($unitPrice < $floor && ! $canBelowFloor) {
                        throw new RuntimeException(sprintf(
                            'Prix sous le plancher pour %s (%.2f < %.2f DH) : autorisation requise.',
                            $product->sku,
                            $unitPrice,
                            $floor,
                        ));
                    }

                    $lineTotal = round($unitPrice * $line['quantity'], 2);
                    $subtotal += $lineTotal;

                    $prepared[] = [
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $unitPrice,
                        'price_type_code' => $priceTypeCode,
                        'line_total' => $lineTotal,
                    ];
                }

                $total = round($subtotal * (1 - $discount / 100), 2);

                $sale = Sale::query()->create([
                    'reference' => $numbers->next('sale'),
                    'type' => $data['type'],
                    'status' => Sale::STATUS_DRAFT,
                    'customer_id' => $data['customer_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'],
                    'user_id' => $request->user()?->id,
                    'subtotal' => round($subtotal, 2),
                    'discount_percent' => $discount,
                    'total' => $total,
                    'note' => $data['note'] ?? null,
                ]);

                foreach ($prepared as $line) {
                    $sale->lines()->create($line);
                }

                return $sale;
            });
        } catch (RuntimeException|NoPriceDefinedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $sale->id, 'reference' => $sale->reference, 'total' => (float) $sale->total]], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $this->assertCanSeeSale(request(), $sale);

        $sale->load(['customer:id,code,name,balance,credit_limit,is_blocked', 'warehouse:id,code', 'lines.product:id,sku,name']);

        return response()->json(['data' => [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'type' => $sale->type,
            'status' => $sale->status,
            'customer' => $sale->customer !== null ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
                'balance' => (float) $sale->customer->balance,
                'credit_limit' => (float) $sale->customer->credit_limit,
                'is_blocked' => $sale->customer->is_blocked,
            ] : null,
            'warehouse' => $sale->warehouse?->code,
            'subtotal' => (float) $sale->subtotal,
            'discount_percent' => (float) $sale->discount_percent,
            'total' => (float) $sale->total,
            'paid_amount' => (float) $sale->paid_amount,
            'payment_status' => $sale->payment_status,
            'confirmed_at' => $sale->confirmed_at?->format('Y-m-d H:i'),
            'note' => $sale->note,
            'lines' => $sale->lines->map(fn ($l): array => [
                'sku' => $l->product?->sku,
                'name' => $l->product?->name,
                'quantity' => $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'price_type_code' => $l->price_type_code,
                'line_total' => (float) $l->line_total,
            ])->values()->all(),
        ]]);
    }

    public function confirm(Request $request, Sale $sale, ConfirmSaleAction $action): JsonResponse
    {
        $allowOverCredit = $request->user()?->can('customer.set_credit_limit') ?? false;

        try {
            $action->execute($sale, $request->user()?->id, $allowOverCredit);
        } catch (RuntimeException|InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($sale->refresh());
    }

    public function cancel(Request $request, Sale $sale, CancelSaleAction $action): JsonResponse
    {
        try {
            $action->execute($sale, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($sale->refresh());
    }

    /**
     * Convertit un devis en vente (facture brouillon reliée au devis).
     * POST /sales/{sale}/convert
     */
    public function convert(Request $request, Sale $sale, DocumentNumberGeneratorInterface $numbers): JsonResponse
    {
        if ($sale->type !== Sale::TYPE_QUOTE) {
            return response()->json(['message' => 'Seul un devis peut être converti en vente.'], 422);
        }

        if ($sale->status === Sale::STATUS_CANCELLED) {
            return response()->json(['message' => 'Ce devis est annulé.'], 422);
        }

        if (Sale::query()->where('quote_id', $sale->id)->exists()) {
            return response()->json(['message' => 'Ce devis a déjà été converti en vente.'], 422);
        }

        $invoice = DB::transaction(function () use ($sale, $numbers, $request): Sale {
            $invoice = Sale::query()->create([
                'reference' => $numbers->next('sale'),
                'type' => Sale::TYPE_INVOICE,
                'status' => Sale::STATUS_DRAFT,
                'customer_id' => $sale->customer_id,
                'quote_id' => $sale->id,
                'warehouse_id' => $sale->warehouse_id,
                'user_id' => $request->user()?->id,
                'subtotal' => $sale->subtotal,
                'discount_percent' => $sale->discount_percent,
                'total' => $sale->total,
                'note' => trim('Issu du devis '.$sale->reference.'. '.($sale->note ?? '')),
            ]);

            foreach ($sale->lines()->get() as $line) {
                $invoice->lines()->create([
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'price_type_code' => $line->price_type_code,
                    'line_total' => $line->line_total,
                ]);
            }

            return $invoice;
        });

        return response()->json(['data' => [
            'id' => $invoice->id,
            'reference' => $invoice->reference,
            'quote_reference' => $sale->reference,
        ]], 201);
    }

    /**
     * PDF de la facture / du devis (avec montants).
     * GET /sales/{sale}/pdf
     */
    public function pdf(Sale $sale): HttpResponse
    {
        $sale->load(['customer', 'warehouse']);
        $lines = $sale->lines()->with('product:id,sku,name')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sale-invoice', [
            'sale' => $sale,
            'lines' => $lines,
        ])->download($sale->reference.'.pdf');
    }

    /**
     * PDF du bon de sortie (quantités seules, aucun montant).
     * GET /sales/{sale}/exit-pdf
     */
    public function exitPdf(Sale $sale): HttpResponse
    {
        $sale->load(['customer', 'warehouse']);
        $lines = $sale->lines()->with('product:id,sku,name')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.delivery-note', [
            'sale' => $sale,
            'lines' => $lines,
        ])->download('BS-'.$sale->reference.'.pdf');
    }
}
