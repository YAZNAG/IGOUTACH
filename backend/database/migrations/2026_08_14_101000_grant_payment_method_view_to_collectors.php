<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Rend la liste des modes de paiement lisible par ceux qui encaissent.
 *
 * « payment_method.view » avait été classé avec les paramètres, donc réservé
 * à l'administrateur. Mais on ne peut pas enregistrer un règlement ni une
 * charge payée sans choisir son mode : le responsable de lieu recevait un 403
 * sur la liste des modes, et l'écran de saisie échouait tout entier — la
 * page des charges de l'application mobile ne s'ouvrait plus du tout.
 *
 * Consulter les modes n'est pas les administrer : « payment_method.manage »
 * reste, lui, réservé.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'payment_method.view'],
            ['display_name' => 'Consulter les modes de paiement', 'module' => 'settings'],
        );

        // Tout rôle qui encaisse ou engage une dépense en a besoin : cibler
        // les rôles par leurs droits plutôt que par leur nom évite d'oublier
        // ceux que le client a créés lui-même.
        $roles = Role::query()
            ->whereHas('permissions', fn ($q) => $q->whereIn('name', [
                'payment.create',
                'expense.create',
                'expense.pay',
            ]))
            ->get();

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        Cache::flush();
    }

    public function down(): void
    {
        // La permission préexiste à cette migration : on ne la supprime pas,
        // on ne fait que retirer les rattachements qu'elle a créés.
        $permission = Permission::where('name', 'payment_method.view')->first();

        if ($permission === null) {
            return;
        }

        Role::query()
            ->where('name', '!=', 'admin')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->detach($permission->id));

        Cache::flush();
    }
};
