<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Droit de régler une charge portée au crédit.
 *
 * Séparé de « expense.approve » à dessein : valider une charge et la payer
 * sont deux actes distincts. Un responsable de lieu doit pouvoir solder ses
 * propres dépenses, mais lui donner « approve » reviendrait à le laisser
 * valider ses propres charges — le contrôle disparaîtrait.
 */
return new class extends Migration
{
    /** Rôles qui gèrent une caisse et doivent pouvoir solder leurs charges. */
    private const ROLES = ['admin', 'manager', 'responsable_lieu'];

    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'expense.pay'],
            ['display_name' => 'Régler une charge', 'module' => 'expense'],
        );

        foreach (self::ROLES as $nom) {
            $role = Role::where('name', $nom)->first();
            $role?->permissions()->syncWithoutDetaching([$permission->id]);
        }

        Cache::flush();
    }

    public function down(): void
    {
        Permission::where('name', 'expense.pay')->delete();
        Cache::flush();
    }
};
