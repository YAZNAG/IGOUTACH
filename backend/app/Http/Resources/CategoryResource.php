<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'requires_serial' => $this->requires_serial,
            'is_active' => $this->is_active,
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at,
        ];
    }
}
