<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Ouvre le prix d'achat aux responsables de lieu.
 *
 * Le prix d'achat était réservé à la direction. Mais un responsable qui vend
 * sans le connaître ne peut ni juger une remise ni refuser une vente à perte :
 * il applique une grille sans savoir ce qu'elle laisse. La page « Prix
 * d'achat » lui est donc ouverte, sur décision explicite du client.
 *
 * Seule la CONSULTATION est accordée. Fixer un prix reste « product.set_price »
 * et demeure réservé : voir un coût et le décider ne sont pas le même métier.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'product.view_cost_price'],
            ['display_name' => "Voir le prix d'achat des articles", 'module' => 'catalog'],
        );

        // Les rôles sont ciblés par ce qu'ils font, pas par leur nom : un rôle
        // créé plus tard pour tenir un point de vente sera couvert de la même
        // façon, sans nouvelle migration.
        $roles = Role::query()
            ->whereHas('permissions', fn ($q) => $q->where('name', 'sale.create'))
            ->get();

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        Cache::flush();
    }

    public function down(): void
    {
        // La permission préexiste : on ne retire que les rattachements posés
        // ici, en laissant l'administrateur intact.
        $permission = Permission::where('name', 'product.view_cost_price')->first();

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
