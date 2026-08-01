<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Models\AuditLog;
use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Support\Query\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AuditController extends Controller
{
    private const PER_PAGE = [20, 50, 100, 200];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->filtered($request)->with('user:id,name,email');

        $sorted = Sortable::apply($query, $request, [
            'created_at' => 'created_at',
            'action' => 'action',
            'module' => 'module',
        ], 'created_at');

        // Par défaut, le plus récent d'abord.
        if ($request->string('sort')->isEmpty()) {
            $sorted->reorder('created_at', 'desc');
        }

        $perPage = in_array($request->integer('per_page'), self::PER_PAGE, true)
            ? $request->integer('per_page')
            : 50;

        return AuditLogResource::collection($sorted->paginate($perPage)->withQueryString());
    }

    /**
     * Distinct des actions et modules présents, pour alimenter les filtres.
     *
     * @return array{actions: array<int, string>, modules: array<int, string>}
     */
    public function filters(): array
    {
        return [
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')
                ->pluck('action')->map(fn ($a): string => (string) $a)->values()->all(),
            'modules' => AuditLog::query()->whereNotNull('module')->select('module')->distinct()->orderBy('module')
                ->pluck('module')->map(fn ($m): string => (string) $m)->values()->all(),
        ];
    }

    public function export(Request $request): BinaryFileResponse
    {
        $headings = ['Date', 'Utilisateur', 'Action', 'Module', 'Description', 'IP'];

        $rows = $this->filtered($request)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get()
            ->map(fn (AuditLog $log): array => [
                $log->created_at?->format('Y-m-d H:i:s') ?? '',
                $log->user !== null ? $log->user->name : '—',
                $log->action,
                $log->module ?? '',
                $log->description ?? '',
                $log->ip_address ?? '',
            ])
            ->all();

        return Excel::download(new ArrayExport($headings, $rows), 'journal-audit.xlsx');
    }

    /**
     * @return Builder<AuditLog>
     */
    private function filtered(Request $request): Builder
    {
        return AuditLog::query()
            ->when($request->string('action')->isNotEmpty(), fn (Builder $q) => $q->where('action', $request->string('action')->value()))
            ->when($request->string('module')->isNotEmpty(), fn (Builder $q) => $q->where('module', $request->string('module')->value()))
            ->when($request->integer('user_id') > 0, fn (Builder $q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->date('from') !== null, fn (Builder $q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->date('to') !== null, fn (Builder $q) => $q->where('created_at', '<=', $request->date('to')?->endOfDay()));
    }
}
