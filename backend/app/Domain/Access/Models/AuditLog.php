<?php

declare(strict_types=1);

namespace App\Domain\Access\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Entrée du journal d'audit (append-only : ni update, ni delete).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $module
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string|null $description
 * @property array<string, mixed>|null $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
final class AuditLog extends Model
{
    /** Journal append-only : pas de colonne updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
