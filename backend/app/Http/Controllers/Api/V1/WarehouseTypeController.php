<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Warehouses\Models\WarehouseType;
use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseTypeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class WarehouseTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return WarehouseTypeResource::collection(
            WarehouseType::query()->orderBy('name')->get(),
        );
    }
}
