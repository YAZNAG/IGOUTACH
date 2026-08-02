<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Purchasing\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource détaillée avec lignes.
 *
 * @mixin PurchaseOrder
 */
final class PurchaseOrderDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
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
            'ordered_at' => $this->ordered_at?->format('Y-m-d H:i:s'),
            'expected_at' => $this->expected_at?->format('Y-m-d'),
            'status' => [
                'id' => $this->status?->id,
                'code' => $this->status?->code,
                'name' => $this->status?->name,
            ],
            'notes' => $this->notes,
            'lines' => PurchaseOrderLineResource::collection($this->lines()->orderBy('position')->get()),
            'created_by' => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
                'email' => $this->createdBy?->email,
            ],
            'can_send' => $this->canSend(),
            'can_approve' => $this->canApprove(),
            'can_receive' => $this->canReceive(),
            'can_cancel' => $this->canCancel(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
