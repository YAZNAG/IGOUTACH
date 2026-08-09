<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Rules\WarehouseAccessible;

/**
 * Charges : catégories, saisie avec justificatif photo (facultatif),
 * validation ou rejet par le responsable.
 */
final class ExpenseController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(['data' => ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        /** @var array{name: string} $data */
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:expense_categories,name']]);

        $category = ExpenseCategory::query()->create(['name' => $data['name']]);

        return response()->json(['data' => ['id' => $category->id, 'name' => $category->name]], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::query()
            ->with(['category:id,name', 'warehouse:id,code', 'user:id,name', 'paymentMethod:id,name'])
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderByDesc('id')
            ->paginate(20);

        $expenses->through(fn (Expense $e): array => [
            'id' => $e->id,
            'label' => $e->label,
            'category' => $e->category?->name,
            'warehouse' => $e->warehouse?->code,
            'user' => $e->user?->name,
            'amount' => (float) $e->amount,
            // Le moyen de reglement est saisi : il doit se lire dans la liste,
            // sinon l'information est enregistree sans jamais etre montree.
            'payment_method' => $e->paymentMethod?->name,
            'expense_date' => $e->expense_date->format('Y-m-d'),
            'has_receipt' => $e->receipt_path !== null,
            'status' => $e->status,
        ]);

        return response()->json([
            'data' => $expenses->items(),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var array{expense_category_id: int, warehouse_id?: int|null, label: string, amount: float, expense_date: string} $data */
        $data = $request->validate([
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'label' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            /** @var UploadedFile $file */
            $file = $request->file('receipt');
            $receiptPath = (string) $file->store('receipts', 'public');
        }

        $expense = Expense::query()->create([
            'expense_category_id' => $data['expense_category_id'],
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'user_id' => (int) $request->user()?->id,
            'label' => $data['label'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'receipt_path' => $receiptPath,
            'status' => Expense::STATUS_PENDING,
        ]);

        return response()->json(['data' => ['id' => $expense->id]], 201);
    }

    public function decide(Request $request, Expense $expense): JsonResponse
    {
        /** @var array{decision: string} $data */
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        if ($expense->status !== Expense::STATUS_PENDING) {
            return response()->json(['message' => 'Charge déjà traitée.'], 422);
        }

        $expense->update([
            'status' => $data['decision'],
            'approved_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => ['id' => $expense->id, 'status' => $expense->status]]);
    }
}
