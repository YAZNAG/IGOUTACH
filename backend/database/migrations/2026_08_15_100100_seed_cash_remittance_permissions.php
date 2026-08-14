<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use App\Domain\Settings\Models\DocumentSequence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Droits sur les remises de caisse.
 *
 * Deux permissions, pas une : celui qui remet et celui qui reçoit ne peuvent
 * pas être la même personne, sinon la confirmation ne vérifie plus rien. Le
 * responsable déclare (« cash.remit »), l'administration confirme
 * (« cash.remit_receive »).
 */
return new class extends Migration
{
    public function up(): void
    {
        $remettre = Permission::firstOrCreate(
            ['name' => 'cash.remit'],
            ['display_name' => 'Remettre la caisse à l’administration', 'module' => 'cash'],
        );

        $confirmer = Permission::firstOrCreate(
            ['name' => 'cash.remit_receive'],
            ['display_name' => 'Confirmer la réception d’une remise', 'module' => 'cash'],
        );

        // Remettre : tout rôle qui encaisse détient une caisse à remettre.
        Role::query()
            ->whereHas('permissions', fn ($q) => $q->where('name', 'payment.create'))
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$remettre->id]));

        // Confirmer : l'administration seule.
        Role::where('name', 'admin')->first()
            ?->permissions()->syncWithoutDetaching([$remettre->id, $confirmer->id]);

        DocumentSequence::updateOrCreate(['key' => 'cash_remittance'], ['prefix' => 'RC-']);

        Cache::flush();
    }

    public function down(): void
    {
        Permission::whereIn('name', ['cash.remit', 'cash.remit_receive'])->delete();
        DocumentSequence::where('key', 'cash_remittance')->delete();

        Cache::flush();
    }
};
