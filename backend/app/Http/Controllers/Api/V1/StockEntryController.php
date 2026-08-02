<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Models\GoodsReceipt;
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
 * Entrées de stock : chaque ligne est un mouvement d'entrée (réception
 * fournisseur, retour client, transfert reçu, régularisation positive).
 * Créées automatiquement par les documents — jamais à la main ici.
 */
final class StockEntryController extends Controller
{
    /**
     * Codes des types de mouvement considérés comme entrées.
     */
    private const ENTRY_CODES = ['in', 'return_in', 'transfer_in'];

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
            ->where(function (Builder $q): void {
                $q->whereHas('movementType', fn (Builder $t) => $t->whereIn('code', self::ENTRY_CODES))
                    ->orWhere(function (Builder $sub): void {
                        // Régularisation d'inventaire positive = entrée.
                        $sub->whereHas('movementType', fn (Builder $t) => $t->where('code', 'adjustment'))
                            ->where('quantity', '>', 0);
                    });
            })
            // Le filtre de dates porte sur la DATE DU MOUVEMENT (created_at porte
            // la date du document : réception, inventaire…).
            ->when($request->string('date_from')->isNotEmpty(), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_to')->value()))
            ->when($request->integer('warehouse_id') > 0, fn (Builder $q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->string('type')->isNotEmpty(), fn (Builder $q) => $q->whereHas('movementType', fn (Builder $t) => $t->where('code', $request->string('type')->value())))
            ->when($request->string('search')->isNotEmpty(), function (Builder $q) use ($request): void {
                $term = $request->string('search')->value();
                $q->whereHas('product', fn (Builder $p) => $p->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            });
    }

    /**
     * Transforme un mouvement en ligne d'affichage.
     *
     * @param  array<int, string>  $receiptNumbers
     * @param  array<int, string>  $userNames
     * @return array<string, mixed>
     */
    private function toRow(StockMovement $movement, array $receiptNumbers, array $userNames): array
    {
        $source = null;
        if ($movement->reference_type === 'goods_receipt' && $movement->reference_id !== null) {
            $source = [
                'type' => 'goods_receipt',
                'id' => (int) $movement->reference_id,
                'label' => $receiptNumbers[(int) $movement->reference_id] ?? "BR #{$movement->reference_id}",
            ];
        } elseif ($movement->reference_type !== null) {
            $source = [
                'type' => $movement->reference_type,
                'id' => $movement->reference_id !== null ? (int) $movement->reference_id : null,
                'label' => str_replace('_', ' ', ucfirst((string) $movement->reference_type)).($movement->reference_id !== null ? " #{$movement->reference_id}" : ''),
            ];
        }

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
            'quantity' => (int) $movement->quantity,
            'unit_cost' => (float) $movement->unit_cost,
            'line_value' => round((float) $movement->unit_cost * (int) $movement->quantity, 2),
            'balance_after' => (int) $movement->balance_after,
            'note' => $movement->note,
            'author' => $movement->user_id !== null ? ($userNames[(int) $movement->user_id] ?? null) : null,
        ];
    }

    /**
     * Charge en une requête les libellés annexes (n° BR, auteurs).
     *
     * @param  iterable<int, StockMovement>  $movements
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function labels(iterable $movements): array
    {
        $receiptIds = [];
        $userIds = [];
        foreach ($movements as $movement) {
            if ($movement->reference_type === 'goods_receipt' && $movement->reference_id !== null) {
                $receiptIds[] = (int) $movement->reference_id;
            }
            if ($movement->user_id !== null) {
                $userIds[] = (int) $movement->user_id;
            }
        }

        $receiptNumbers = $receiptIds === [] ? [] : GoodsReceipt::whereIn('id', array_unique($receiptIds))->pluck('number', 'id')->all();
        $userNames = $userIds === [] ? [] : User::whereIn('id', array_unique($userIds))->pluck('name', 'id')->all();

        return [$receiptNumbers, $userNames];
    }

    /**
     * GET /stock/entries — liste paginée + totaux sur l'ensemble filtré.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request);

        // Totaux sur TOUT l'ensemble filtré, pas seulement la page.
        $totals = (clone $query)
            ->selectRaw('COUNT(*) as lines_count, COALESCE(SUM(quantity), 0) as total_quantity, COALESCE(SUM(quantity * unit_cost), 0) as total_value')
            ->withoutEagerLoads()
            ->first();

        $perPage = in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20;
        $paginator = $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);

        [$receiptNumbers, $userNames] = $this->labels($paginator->items());

        $rows = array_map(
            fn (StockMovement $m): array => $this->toRow($m, $receiptNumbers, $userNames),
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
     * GET /stock/entries/{movement} — détail d'une entrée.
     */
    public function show(int $movement): JsonResponse
    {
        /** @var StockMovement $found */
        $found = $this->entriesOnly()->with([
            'product:id,sku,name,unit_id',
            'product.unit:id,code',
            'movementType:id,code,name',
            'warehouse:id,code,name',
        ])->findOrFail($movement);

        [$receiptNumbers, $userNames] = $this->labels([$found]);

        return response()->json(['data' => $this->toRow($found, $receiptNumbers, $userNames)]);
    }

    /**
     * @return Builder<StockMovement>
     */
    private function entriesOnly(): Builder
    {
        return StockMovement::query()->where(function (Builder $q): void {
            $q->whereHas('movementType', fn (Builder $t) => $t->whereIn('code', self::ENTRY_CODES))
                ->orWhere(function (Builder $sub): void {
                    $sub->whereHas('movementType', fn (Builder $t) => $t->where('code', 'adjustment'))
                        ->where('quantity', '>', 0);
                });
        });
    }

    /**
     * GET /stock/entries/export?format=pdf|xlsx — mêmes filtres que la liste.
     */
    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $movements = $this->baseQuery($request)->orderByDesc('created_at')->orderByDesc('id')->get();

        [$receiptNumbers, $userNames] = $this->labels($movements);

        $headings = ['Date', 'N°', 'Type', 'Document', 'Lieu', 'Référence', 'Désignation', 'Qté', 'PU (DH)', 'Valeur (DH)', 'Solde après', 'Auteur'];

        $rows = $movements->map(function (StockMovement $m) use ($receiptNumbers, $userNames): array {
            $row = $this->toRow($m, $receiptNumbers, $userNames);

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
        $title = 'Entrées de stock'.($period !== '' ? " ({$period})" : '');
        $filename = 'IGOUTECH_entrees-stock'.($request->string('date_from')->isNotEmpty() ? '_'.$request->string('date_from')->value() : '').($request->string('date_to')->isNotEmpty() ? '_'.$request->string('date_to')->value() : '');

        if ($request->string('format')->value() === 'pdf') {
            return Pdf::loadHtml(HtmlTable::render($title, $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), $filename.'.xlsx');
    }
}
