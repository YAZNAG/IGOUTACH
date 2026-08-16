<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Droit de supprimer une charge.
 *
 * Séparé de la saisie : n'importe qui peut se tromper en saisissant, mais
 * effacer une dépense déjà réglée déplace de l'argent — la somme revient au
 * tiroir. Ce geste revient à celui qui répond de la caisse, pas à celui qui
 * a saisi la ligne.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'expense.delete'],
            ['display_name' => 'Supprimer une charge', 'module' => 'expense'],
        );

        // Ceux qui règlent une charge sont ceux qui tiennent la caisse : ce
        // sont eux qui doivent pouvoir défaire une ligne fausse.
        Role::query()
            ->whereHas('permissions', fn ($q) => $q->whereIn('name', ['expense.pay', 'expense.approve']))
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));

        Cache::flush();
    }

    public function down(): void
    {
        Permission::where('name', 'expense.delete')->delete();

        Cache::flush();
    }
};
