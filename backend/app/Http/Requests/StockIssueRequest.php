<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Stock\Actions\IssueStockAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\WarehouseAccessible;

final class StockIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.issue') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'reason_code' => ['required', Rule::in(array_keys(IssueStockAction::REASONS))],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
