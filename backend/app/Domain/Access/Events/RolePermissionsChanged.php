<?php

declare(strict_types=1);

namespace App\Domain\Access\Events;

use App\Domain\Access\Models\Role;
use Illuminate\Foundation\Events\Dispatchable;

final class RolePermissionsChanged
{
    use Dispatchable;

    public function __construct(public readonly Role $role) {}
}
