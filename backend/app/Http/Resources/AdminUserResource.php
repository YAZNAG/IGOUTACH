<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Access\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse !== null ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'invited' => $this->email_verified_at === null,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'level' => $role->level,
            ])->all()),
        ];
    }
}
