<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Domain\Warehouses\Actions\CreateWarehouseAction;
use App\Domain\Warehouses\Actions\ToggleWarehouseAction;
use App\Domain\Warehouses\Actions\UpdateWarehouseAction;
use App\Domain\Warehouses\DTOs\WarehouseData;
use App\Domain\Warehouses\Exceptions\WarehouseInUseException;
use App\Domain\Warehouses\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignWarehouseUsersRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

final class WarehouseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        // Un utilisateur mono-lieu ne voit que le sien. C'est la source de
        // tous les sélecteurs de lieu de l'application : le filtrer ici évite
        // que les autres lieux apparaissent dans les listes déroulantes, les
        // filtres et les formulaires, écran par écran.
        $warehouses = Warehouse::query()
            ->with('type:id,code,name')
            ->when(
                $user !== null && ! $user->can('stock.view_global'),
                fn ($q) => $q->where('id', $user->getAttribute('warehouse_id')),
            )
            ->orderBy('code')
            ->paginate(20);

        return WarehouseResource::collection($warehouses);
    }

    public function store(StoreWarehouseRequest $request, CreateWarehouseAction $action): JsonResponse
    {
        $warehouse = $action->execute(WarehouseData::fromArray($request->validated()));

        return WarehouseResource::make($warehouse->load('type'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Un utilisateur mono-lieu ne consulte que le sien : sans ce contrôle,
     * il suffirait de changer l'identifiant dans l'URL pour lire le detail,
     * les comptes rattachés et la valeur du stock d'un autre lieu.
     */
    private function assertLieuAccessible(Warehouse $warehouse): void
    {
        $user = auth()->user();

        if ($user === null || $user->can('stock.view_global')) {
            return;
        }

        abort_if((int) $warehouse->id !== (int) $user->getAttribute('warehouse_id'), 403);
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        $this->assertLieuAccessible($warehouse);

        return WarehouseResource::make($warehouse->load('type'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse, UpdateWarehouseAction $action): WarehouseResource
    {
        $updated = $action->execute($warehouse, WarehouseData::fromArray($request->validated()));

        return WarehouseResource::make($updated->load('type'));
    }

    public function toggle(Warehouse $warehouse, ToggleWarehouseAction $action): JsonResponse|WarehouseResource
    {
        try {
            $action->execute($warehouse);
        } catch (WarehouseInUseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return WarehouseResource::make($warehouse->load('type'));
    }

    /**
     * Utilisateurs rattachés au lieu.
     */
    public function users(Warehouse $warehouse): JsonResponse
    {
        $this->assertLieuAccessible($warehouse);

        return $this->reponseUtilisateurs($warehouse);
    }

    /**
     * Contenu seul, sans contrôle d'accès : le rattachement le réutilise après
     * sa propre autorisation. Le garde reste au point d'entrée HTTP, sinon il
     * se déclencherait sur un appel interne déjà légitime.
     */
    private function reponseUtilisateurs(Warehouse $warehouse): JsonResponse
    {
        $users = $warehouse->users()->get(['id', 'name', 'email', 'is_active'])->map(fn ($u): array => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'is_active' => $u->is_active,
        ]);

        return response()->json(['data' => $users]);
    }

    /**
     * Rattache un ensemble d'utilisateurs au lieu (et détache ceux retirés).
     */
    public function assignUsers(AssignWarehouseUsersRequest $request, Warehouse $warehouse, AuditLoggerInterface $audit): JsonResponse
    {
        /** @var array{user_ids: list<int>} $data */
        $data = $request->validated();
        $userIds = $data['user_ids'];

        if ($warehouse->isVehicle() && count($userIds) > 1) {
            return response()->json(['message' => 'Un véhicule ne peut avoir qu\'un seul vendeur rattaché.'], 422);
        }

        DB::transaction(function () use ($warehouse, $userIds): void {
            // Détache les utilisateurs actuellement rattachés mais retirés de la liste.
            User::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereNotIn('id', $userIds === [] ? [0] : $userIds)
                ->update(['warehouse_id' => null]);

            if ($userIds !== []) {
                User::query()->whereIn('id', $userIds)->update(['warehouse_id' => $warehouse->id]);
            }
        });

        $audit->log(
            action: 'warehouse.users_assigned',
            description: 'Utilisateurs rattachés au lieu '.$warehouse->code,
            entityType: $warehouse::class,
            entityId: $warehouse->id,
            module: 'warehouses',
        );

        return $this->reponseUtilisateurs($warehouse);
    }

    /**
     * Indicateurs de stock du lieu (valeur, références, sous seuil, ruptures, dernier mouvement).
     */
    public function summary(Warehouse $warehouse): JsonResponse
    {
        $this->assertLieuAccessible($warehouse);

        $inStock = DB::table('stocks')->where('warehouse_id', $warehouse->id)->where('quantity', '>', 0);

        $value = (float) (clone $inStock)->sum(DB::raw('quantity * average_cost'));
        $references = (clone $inStock)->count();

        $belowThreshold = DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->where('stocks.warehouse_id', $warehouse->id)
            ->whereColumn('stocks.quantity', '<', 'products.min_stock')
            ->where('stocks.quantity', '>', 0)
            ->count();

        $ruptures = DB::table('stocks')->where('warehouse_id', $warehouse->id)->where('quantity', '<=', 0)->count();

        $lastMovement = DB::table('stock_movements')
            ->where('warehouse_id', $warehouse->id)
            ->max('created_at');

        return response()->json([
            'data' => [
                'stock_value' => round($value, 2),
                'references' => $references,
                'below_threshold' => $belowThreshold,
                'ruptures' => $ruptures,
                'last_movement_at' => $lastMovement,
                'seller_missing' => $warehouse->isVehicle() && $warehouse->users()->count() === 0,
            ],
        ]);
    }
}
