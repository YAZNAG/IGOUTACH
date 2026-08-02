<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Models\Permission;
use Illuminate\Database\Seeder;

class PurchasePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'purchase.view', 'display_name' => 'Voir les bons de commande', 'module' => 'purchase'],
            ['name' => 'purchase.create', 'display_name' => 'Créer/Envoyer les bons de commande', 'module' => 'purchase'],
            ['name' => 'purchase.approve', 'display_name' => 'Approuver les bons de commande', 'module' => 'purchase'],
            ['name' => 'purchase.receive', 'display_name' => 'Recevoir des bons de commande', 'module' => 'purchase'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
