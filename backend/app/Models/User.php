<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Access\Models\UserPermissionPivot;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property bool $is_active
 * @property int $failed_attempts
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property int|null $warehouse_id
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'warehouse_id',
        'is_active',
        'last_login_at',
        'failed_attempts',
        'locked_until',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Le compte est-il temporairement verrouillé (trop de tentatives échouées) ?
     */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot(['assigned_at', 'assigned_by']);
    }

    /**
     * Dérogations de permissions propres à l'utilisateur (ajout ou retrait).
     *
     * @return BelongsToMany<Permission, $this, UserPermissionPivot, 'pivot'>
     */
    public function permissionOverrides(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission')
            ->using(UserPermissionPivot::class)
            ->withPivot(['is_granted', 'granted_by', 'reason', 'expires_at'])
            ->withTimestamps();
    }

    public function hasGlobalAccess(): bool
    {
        return $this->warehouse_id === null;
    }
}
