<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Permissions du module réceptions (receipt.view / receipt.create),
 * attachées au rôle admin (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'receipt.view', 'display_name' => 'Voir les bons de réception', 'module' => 'purchases'],
            ['name' => 'receipt.create', 'display_name' => 'Créer un bon de réception', 'module' => 'purchases'],
        ];

        $ids = [];
        foreach ($permissions as $permission) {
            $ids[] = Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            )->id;
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin !== null) {
            $admin->permissions()->syncWithoutDetaching($ids);
        }

        Cache::flush();
    }

    public function down(): void
    {
        // Les permissions restent : elles peuvent être portées par d'autres rôles.
    }
};
