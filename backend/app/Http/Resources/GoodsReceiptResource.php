<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Purchasing\Models\GoodsReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ligne de liste des bons de réception.
 *
 * @mixin GoodsReceipt
 */
final class GoodsReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'lines_count' => (int) ($this->lines_count ?? $this->lines()->count()),
            'total_quantity' => (int) ($this->total_quantity ?? 0),
            'total_amount' => round((float) ($this->total_amount ?? 0), 2),
            'created_by' => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
                'email' => $this->createdBy?->email,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
