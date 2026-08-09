<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSerial;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Rules\WarehouseAccessible;

/**
 * Numéros de série d'un article : liste, ajout en lot, suppression (non vendus).
 */
final class ProductSerialController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $serials = $product->serials()
            ->with('warehouse:id,code')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (ProductSerial $s): array => [
                'id' => $s->id,
                'serial_number' => $s->serial_number,
                'warehouse' => $s->warehouse?->code,
                'is_sold' => $s->is_sold,
            ])->values();

        return response()->json(['data' => $serials]);
    }

    /**
     * Ajout en lot : un numéro par ligne, doublons ignorés.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        /** @var array{serials: string, warehouse_id?: int|null} $data */
        $data = $request->validate([
            'serials' => ['required', 'string', 'max:20000'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
        ]);

        $lines = array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $data['serials']) ?: [],
        ), fn (string $line): bool => $line !== ''));
        $numbers = array_values(array_unique($lines));

        $created = 0;
        DB::transaction(function () use ($product, $numbers, $data, &$created): void {
            foreach ($numbers as $number) {
                $exists = ProductSerial::query()->where('serial_number', $number)->exists();
                if ($exists) {
                    continue;
                }

                $product->serials()->create([
                    'serial_number' => $number,
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                ]);
                $created++;
            }
        });

        return response()->json(['data' => ['created' => $created, 'skipped' => count($lines) - $created]], 201);
    }

    public function destroy(Product $product, ProductSerial $serial): JsonResponse
    {
        abort_if($serial->product_id !== $product->id, 404);

        if ($serial->is_sold) {
            return response()->json(['message' => 'Un numéro de série vendu ne peut pas être supprimé.'], 422);
        }

        $serial->delete();

        return response()->json(['message' => 'Numéro de série supprimé.']);
    }
}
