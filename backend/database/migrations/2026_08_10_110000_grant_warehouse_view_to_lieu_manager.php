<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Le responsable de lieu doit pouvoir lire SON lieu : sans cela, les
 * sélecteurs de lieu de l'application restent vides et il ne peut ni créer
 * d'inventaire ni demander un transfert.
 *
 * Sans risque : la liste ne renvoie que son lieu, et le détail, les comptes
 * rattachés et la valorisation sont refusés sur tout autre lieu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'responsable_lieu')->first();
        $permission = Permission::where('name', 'warehouse.view')->first();

        if ($role !== null && $permission !== null) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        $role = Role::where('name', 'responsable_lieu')->first();
        $permission = Permission::where('name', 'warehouse.view')->first();

        if ($role !== null && $permission !== null) {
            $role->permissions()->detach($permission->id);
        }
    }
};
