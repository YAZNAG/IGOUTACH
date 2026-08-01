<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PermissionController extends Controller
{
    /**
     * Permissions groupées par module (pour la matrice permissions × rôles).
     */
    public function index(): JsonResponse
    {
        $groups = Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'module'])
            ->groupBy('module')
            ->map(fn ($items, string $module): array => [
                'module' => $module,
                'permissions' => $items->map(fn (Permission $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'display_name' => $p->display_name,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return response()->json(['data' => $groups]);
    }
}
