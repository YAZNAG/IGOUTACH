<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, display_name: string, description: string, level: int}>
     */
    public const ROLES = [
        ['name' => 'admin', 'display_name' => 'Administrateur', 'description' => 'Accès complet et vue consolidée', 'level' => 100],
        ['name' => 'manager', 'display_name' => 'Responsable', 'description' => 'Gestion d\'un lieu (dépôt ou point de vente)', 'level' => 50],
        ['name' => 'seller', 'display_name' => 'Vendeur', 'description' => 'Vendeur itinérant, stock embarqué', 'level' => 10],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'is_system' => true,
                    'level' => $role['level'],
                ],
            );
        }
    }
}
