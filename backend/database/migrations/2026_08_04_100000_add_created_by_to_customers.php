<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Créateur du client : les non-admins ne voient que leurs propres clients.
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });

        // Permission « voir tous les clients » — réservée à l'admin par défaut.
        $permission = Permission::firstOrCreate(
            ['name' => 'customer.view_all'],
            ['display_name' => 'Voir tous les clients', 'module' => 'customer'],
        );

        $admin = Role::where('name', 'admin')->first();
        if ($admin !== null) {
            $admin->permissions()->syncWithoutDetaching([$permission->id]);
        }

        Cache::flush();
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Permission::where('name', 'customer.view_all')->delete();
        Cache::flush();
    }
};
