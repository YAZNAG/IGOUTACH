<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Catalog\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxRate
 */
final class TaxRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rate' => (float) $this->rate,
            'label' => $this->label,
            'is_default' => $this->is_default,
            'position' => $this->position,
            'is_active' => $this->is_active,
        ];
    }
}
