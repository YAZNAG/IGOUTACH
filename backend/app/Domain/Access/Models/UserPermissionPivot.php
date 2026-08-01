<?php

declare(strict_types=1);

namespace App\Domain\Access\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot typé de la relation user ↔ permission (dérogations individuelles).
 *
 * @property bool $is_granted
 * @property int|null $granted_by
 * @property string|null $reason
 * @property string|null $expires_at
 */
class UserPermissionPivot extends Pivot
{
    protected function casts(): array
    {
        return [
            'is_granted' => 'boolean',
        ];
    }
}
