<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
final class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'level' => $this->level,
            'users_count' => $this->whenCounted('users'),
            'permissions_count' => $this->whenCounted('permissions'),
            'permission_ids' => $this->whenLoaded('permissions', fn () => $this->permissions->map(fn (Permission $p): int => $p->id)->all()),
        ];
    }
}
