<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Le responsable de lieu arbitre les demandes portant sur SON stock.
 *
 * La permission dit qu'il peut trancher une demande ; le contrôleur restreint
 * ensuite à son propre lieu source. Comme partout ici, la permission ouvre la
 * porte et le périmètre est vérifié dans le code, jamais par un nom de rôle.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'responsable_lieu')->first();
        $permission = Permission::where('name', 'transfer.approve')->first();

        if ($role !== null && $permission !== null) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        $role = Role::where('name', 'responsable_lieu')->first();
        $permission = Permission::where('name', 'transfer.approve')->first();

        if ($role !== null && $permission !== null) {
            $role->permissions()->detach($permission->id);
        }
    }
};
