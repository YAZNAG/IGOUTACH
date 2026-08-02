<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Models\SupplierContact;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contacts multiples d'un fournisseur.
 */
final class SupplierContactController extends Controller
{
    public function index(Supplier $supplier): JsonResponse
    {
        return response()->json(['data' => $this->list($supplier)]);
    }

    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $supplier->contacts()->create($this->validated($request));

        return response()->json(['data' => $this->list($supplier)], 201);
    }

    public function update(Request $request, Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        abort_if($contact->supplier_id !== $supplier->id, 404);

        $contact->update($this->validated($request));

        return response()->json(['data' => $this->list($supplier)]);
    }

    public function destroy(Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        abort_if($contact->supplier_id !== $supplier->id, 404);

        $contact->delete();

        return response()->json(['data' => $this->list($supplier)]);
    }

    /**
     * @return array{name: string, role: string|null, phone: string|null, email: string|null}
     */
    private function validated(Request $request): array
    {
        /** @var array{name: string, role?: string|null, phone?: string|null, email?: string|null} $data */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        return [
            'name' => $data['name'],
            'role' => $data['role'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function list(Supplier $supplier): array
    {
        return $supplier->contacts()->orderBy('name')->get()->map(fn (SupplierContact $c): array => [
            'id' => $c->id,
            'name' => $c->name,
            'role' => $c->role,
            'phone' => $c->phone,
            'email' => $c->email,
        ])->values()->all();
    }
}
