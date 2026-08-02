<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'receipt.pay'],
            ['display_name' => 'Régler les crédits fournisseurs', 'module' => 'receipt'],
        );

        $admin = Role::where('name', 'admin')->first();
        if ($admin !== null) {
            $admin->permissions()->syncWithoutDetaching([$permission->id]);
        }

        Cache::flush();
    }

    public function down(): void
    {
        Permission::where('name', 'receipt.pay')->delete();
        Cache::flush();
    }
};
