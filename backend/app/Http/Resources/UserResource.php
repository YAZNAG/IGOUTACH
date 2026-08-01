<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resolver = app(PermissionResolverInterface::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'warehouse_id' => $this->warehouse_id,
            'is_active' => $this->is_active,
            'roles' => $this->roles->pluck('name')->all(),
            'permissions' => $resolver->effectivePermissions($this->resource),
        ];
    }
}
