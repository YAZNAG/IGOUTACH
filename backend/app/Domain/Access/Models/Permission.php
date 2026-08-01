<?php

declare(strict_types=1);

namespace App\Domain\Access\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $module
 * @property string|null $description
 * @property-read UserPermissionPivot $pivot
 */
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'module',
        'description',
    ];

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return PermissionFactory::new();
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
