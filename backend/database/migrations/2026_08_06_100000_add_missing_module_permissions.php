<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Complète le référentiel de permissions par module.
 *
 * Deux permissions étaient exigées par les routes sans exister en base
 * (purchase.view, purchase.approve) : les écrans concernés étaient donc
 * invisibles pour tout le monde, y compris l'administrateur.
 *
 * Les autres sont de nouveaux découpages demandés (devis, retours, crédits,
 * cycle de caisse). Chaque nouveauté est attribuée aux rôles qui possèdent
 * déjà la permission équivalente, pour ne retirer l'accès à personne.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{module: string, label: string, herite_de: string|null}>
     */
    private const NOUVELLES = [
        // Manquantes en base alors que les routes les exigent.
        'purchase.view' => ['module' => 'purchases', 'label' => 'Consulter les bons de commande', 'herite_de' => 'purchase.create'],
        'purchase.approve' => ['module' => 'purchases', 'label' => 'Approuver un bon de commande', 'herite_de' => null],

        // Découpages demandés.
        'purchase.return' => ['module' => 'purchases', 'label' => 'Enregistrer un retour fournisseur', 'herite_de' => 'stock.issue'],
        'quote.create' => ['module' => 'sales', 'label' => 'Créer et gérer les devis', 'herite_de' => 'sale.create'],
        'sale.return' => ['module' => 'sales', 'label' => 'Enregistrer un retour client', 'herite_de' => 'stock.entry'],
        'credit.view' => ['module' => 'payments', 'label' => 'Consulter les crédits clients', 'herite_de' => 'payment.view'],
        'cash.open' => ['module' => 'payments', 'label' => 'Ouvrir une session de caisse', 'herite_de' => 'cash.manage'],
        'cash.close_variance' => ['module' => 'payments', 'label' => 'Clôturer une caisse avec écart', 'herite_de' => 'cash.manage'],
    ];

    public function up(): void
    {
        foreach (self::NOUVELLES as $name => $meta) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $meta['label'], 'module' => $meta['module']],
            );

            // Les rôles qui avaient déjà l'équivalent conservent l'accès.
            if ($meta['herite_de'] !== null) {
                $source = Permission::where('name', $meta['herite_de'])->first();
                if ($source !== null) {
                    $roleIds = $source->roles()->pluck('roles.id')->all();
                    foreach ($roleIds as $roleId) {
                        Role::find($roleId)?->permissions()->syncWithoutDetaching([$permission->id]);
                    }
                }
            }
        }

        // L'administrateur possède toujours l'intégralité des permissions.
        $admin = Role::where('name', 'admin')->first();
        if ($admin !== null) {
            $admin->permissions()->sync(Permission::pluck('id')->all());
        }

        Cache::flush();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::NOUVELLES))->delete();
        Cache::flush();
    }
};
