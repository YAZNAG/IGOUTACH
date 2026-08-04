<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\StockMovement;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Export\HtmlTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Sorties de stock : chaque ligne est un mouvement de sortie (vente,
 * transfert expédié, régularisation négative). Quantités stockées en
 * négatif — affichées en valeur absolue, valorisées au CMUP de sortie.
 */
final class StockExitController extends Controller
{
    /**
     * @return Builder<StockMovement>
     */
    private function baseQuery(Request $request): Builder
    {
        return StockMovement::query()
            ->with([
                'product:id,sku,name,unit_id',
                'product.unit:id,code',
                'movementType:id,code,name',
                'warehouse:id,code,name',
            ])
            // Toute sortie a une quantité négative (out, transfert, régularisation à la baisse).
            ->where('quantity', '<', 0)
            // Le filtre de dates porte sur la DATE DU MOUVEMENT.
            ->when($request->string('date_from')->isNotEmpty(), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_to')->value()))
            ->when($request->integer('warehouse_id') > 0, fn (Builder $q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->string('type')->isNotEmpty(), fn (Builder $q) => $q->whereHas('movementType', fn (Builder $t) => $t->where('code', $request->string('type')->value())))
            ->when($request->string('search')->isNotEmpty(), function (Builder $q) use ($request): void {
                $term = $request->string('search')->value();
                $q->whereHas('product', fn (Builder $p) => $p->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            // Sorties liées aux ventes d'un client donné.
            ->when($request->integer('customer_id') > 0, function (Builder $q) use ($request): void {
                $q->whereIn('reference_type', [Sale::class, 'sale'])
                    ->whereIn('reference_id', Sale::query()
                        ->where('customer_id', $request->integer('customer_id'))
                        ->select('id'));
            });
    }

    /**
     * @param  array<int, string>  $saleReferences
     * @param  array<int, string>  $userNames
     * @return array<string, mixed>
     */
    private function toRow(StockMovement $movement, array $saleReferences, array $userNames): array
    {
        $source = null;
        $isSale = in_array($movement->reference_type, [Sale::class, 'sale'], true);
        if ($isSale && $movement->reference_id !== null) {
            $source = [
                'type' => 'sale',
                'id' => (int) $movement->reference_id,
                'label' => $saleReferences[(int) $movement->reference_id] ?? "Vente #{$movement->reference_id}",
            ];
        } elseif ($movement->reference_type !== null) {
            $shortType = str_contains((string) $movement->reference_type, '\\')
                ? strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', class_basename((string) $movement->reference_type)))
                : (string) $movement->reference_type;
            $source = [
                'type' => $shortType,
                'id' => $movement->reference_id !== null ? (int) $movement->reference_id : null,
                'label' => str_replace('_', ' ', ucfirst($shortType)).($movement->reference_id !== null ? " #{$movement->reference_id}" : ''),
            ];
        }

        $quantity = abs((int) $movement->quantity);

        return [
            'id' => $movement->id,
            'date' => $movement->created_at?->format('Y-m-d H:i:s'),
            'type' => [
                'code' => $movement->movementType?->code,
                'name' => $movement->movementType?->name,
            ],
            'source' => $source,
            'warehouse' => [
                'id' => $movement->warehouse?->id,
                'code' => $movement->warehouse?->code,
                'name' => $movement->warehouse?->name,
            ],
            'product' => [
                'id' => $movement->product?->id,
                'sku' => $movement->product?->sku,
                'name' => $movement->product?->name,
                'unit' => $movement->product?->unit?->code,
            ],
            'quantity' => $quantity,
            'unit_cost' => (float) $movement->unit_cost,
            'line_value' => round((float) $movement->unit_cost * $quantity, 2),
            'balance_after' => (int) $movement->balance_after,
            'note' => $movement->note,
            'author' => $movement->user_id !== null ? ($userNames[(int) $movement->user_id] ?? null) : null,
        ];
    }

    /**
     * @param  iterable<int, StockMovement>  $movements
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function labels(iterable $movements): array
    {
        $saleIds = [];
        $userIds = [];
        foreach ($movements as $movement) {
            if (in_array($movement->reference_type, [Sale::class, 'sale'], true) && $movement->reference_id !== null) {
                $saleIds[] = (int) $movement->reference_id;
            }
            if ($movement->user_id !== null) {
                $userIds[] = (int) $movement->user_id;
            }
        }

        $saleReferences = $saleIds === [] ? [] : Sale::whereIn('id', array_unique($saleIds))->pluck('reference', 'id')->all();
        $userNames = $userIds === [] ? [] : User::whereIn('id', array_unique($userIds))->pluck('name', 'id')->all();

        return [$saleReferences, $userNames];
    }

    /**
     * GET /stock/exits — liste paginée + totaux sur l'ensemble filtré.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request);

        $totals = (clone $query)
            ->selectRaw('COUNT(*) as lines_count, COALESCE(SUM(ABS(quantity)), 0) as total_quantity, COALESCE(SUM(ABS(quantity) * unit_cost), 0) as total_value')
            ->withoutEagerLoads()
            ->first();

        $perPage = in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20;
        $paginator = $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);

        [$saleReferences, $userNames] = $this->labels($paginator->items());

        $rows = array_map(
            fn (StockMovement $m): array => $this->toRow($m, $saleReferences, $userNames),
            $paginator->items(),
        );

        return response()->json([
            'data' => $rows,
            'totals' => [
                'lines_count' => (int) ($totals->lines_count ?? 0),
                'total_quantity' => (int) ($totals->total_quantity ?? 0),
                'total_value' => round((float) ($totals->total_value ?? 0), 2),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /stock/exits/{movement} — détail d'une sortie.
     */
    public function show(int $movement): JsonResponse
    {
        /** @var StockMovement $found */
        $found = StockMovement::query()
            ->where('quantity', '<', 0)
            ->with([
                'product:id,sku,name,unit_id',
                'product.unit:id,code',
                'movementType:id,code,name',
                'warehouse:id,code,name',
            ])->findOrFail($movement);

        [$saleReferences, $userNames] = $this->labels([$found]);

        return response()->json(['data' => $this->toRow($found, $saleReferences, $userNames)]);
    }

    /**
     * GET /stock/exits/export?format=pdf|xlsx — mêmes filtres que la liste.
     */
    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $movements = $this->baseQuery($request)->orderByDesc('created_at')->orderByDesc('id')->get();

        [$saleReferences, $userNames] = $this->labels($movements);

        $headings = ['Date', 'N°', 'Type', 'Document', 'Lieu', 'Référence', 'Désignation', 'Qté', 'CMUP (DH)', 'Valeur (DH)', 'Solde après', 'Auteur'];

        $rows = $movements->map(function (StockMovement $m) use ($saleReferences, $userNames): array {
            $row = $this->toRow($m, $saleReferences, $userNames);

            return [
                $row['date'],
                $row['id'],
                $row['type']['name'],
                $row['source']['label'] ?? '—',
                $row['warehouse']['code'].' '.$row['warehouse']['name'],
                $row['product']['sku'],
                $row['product']['name'],
                $row['quantity'],
                number_format($row['unit_cost'], 2, '.', ''),
                number_format($row['line_value'], 2, '.', ''),
                $row['balance_after'],
                $row['author'] ?? '—',
            ];
        })->values()->all();

        $period = trim($request->string('date_from')->value().' → '.$request->string('date_to')->value(), ' →');
        $title = 'Sorties de stock'.($period !== '' ? " ({$period})" : '');
        $filename = 'IGOUTECH_sorties-stock'.($request->string('date_from')->isNotEmpty() ? '_'.$request->string('date_from')->value() : '').($request->string('date_to')->isNotEmpty() ? '_'.$request->string('date_to')->value() : '');

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render($title, $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), $filename.'.xlsx');
    }
}
