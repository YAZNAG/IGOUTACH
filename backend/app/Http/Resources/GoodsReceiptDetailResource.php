<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\GoodsReceiptLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Détail d'un bon de réception avec lignes valorisées.
 *
 * @mixin GoodsReceipt
 */
final class GoodsReceiptDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lines = $this->lines()->with('product')->orderBy('position')->get();

        return [
            'id' => $this->id,
            'number' => $this->number,
            'purchase_order' => $this->purchaseOrder !== null ? [
                'id' => $this->purchaseOrder->id,
                'number' => $this->purchaseOrder->number,
            ] : null,
            'supplier' => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
                'code' => $this->supplier?->code,
            ],
            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
                'code' => $this->warehouse?->code,
            ],
            'received_at' => $this->received_at?->format('Y-m-d H:i:s'),
            'invoice_number' => $this->invoice_number,
            'notes' => $this->notes,
            'lines' => $lines->map(fn (GoodsReceiptLine $line): array => [
                'id' => $line->id,
                'purchase_order_line_id' => $line->purchase_order_line_id,
                'product' => [
                    'id' => $line->product?->id,
                    'sku' => $line->product?->sku,
                    'name' => $line->product?->name,
                ],
                'quantity' => $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'line_total' => $line->lineTotal(),
                'over_receipt_reason' => $line->over_receipt_reason,
                'position' => $line->position,
            ])->all(),
            'total_quantity' => (int) $lines->sum('quantity'),
            'total_amount' => round($lines->sum(fn (GoodsReceiptLine $line): float => $line->lineTotal()), 2),
            'created_by' => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
                'email' => $this->createdBy?->email,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
