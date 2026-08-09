<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demande de transfert.
 *
 * Un responsable de lieu ne peut pas se servir dans le stock d'un autre : il
 * demande, la direction accorde. La demande ne déplace aucune marchandise —
 * seule l'approbation crée le mouvement, sinon le stock source diminuerait
 * sur simple demande.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['code' => 'requested', 'name' => 'Demandé'],
            ['code' => 'refused', 'name' => 'Refusé'],
        ] as $statut) {
            DB::table('transfer_statuses')->updateOrInsert(
                ['code' => $statut['code']],
                ['name' => $statut['name'], 'created_at' => now(), 'updated_at' => now()],
            );
        }

        if (! Schema::hasColumn('transfers', 'requested_by')) {
            Schema::table('transfers', function (Blueprint $table): void {
                $table->foreignId('requested_by')->nullable()->after('created_by')
                    ->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->after('requested_by')
                    ->constrained('users')->nullOnDelete();
                $table->timestamp('requested_at')->nullable()->after('sent_at');
                $table->timestamp('approved_at')->nullable()->after('requested_at');
                $table->string('refusal_reason')->nullable()->after('note');
            });
        }

        $this->seedPermissions();
    }

    private function seedPermissions(): void
    {
        $nouvelles = [
            'transfer.request' => [
                'module' => 'stock',
                'label' => 'Demander un transfert vers son lieu',
                'herite_de' => 'transfer.create',
            ],
            'transfer.approve' => [
                'module' => 'stock',
                'label' => 'Approuver ou refuser une demande de transfert',
                'herite_de' => 'transfer.create',
            ],
        ];

        foreach ($nouvelles as $name => $meta) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $meta['label'], 'module' => $meta['module']],
            );

            $source = Permission::where('name', $meta['herite_de'])->first();

            if ($source !== null) {
                foreach (DB::table('role_permission')->where('permission_id', $source->id)->pluck('role_id') as $roleId) {
                    Role::find($roleId)?->permissions()->syncWithoutDetaching([$permission->id]);
                }
            }
        }

        Role::where('name', 'admin')->first()?->permissions()->sync(Permission::pluck('id')->all());

        // Le responsable de lieu demande, il n'exécute pas : il perd
        // transfer.create et transfer.approve, garde transfer.request et la
        // réception de ce qui lui est envoyé.
        $responsable = Role::where('name', 'responsable_lieu')->first();

        if ($responsable !== null) {
            $aRetirer = Permission::whereIn('name', [
                'transfer.create',
                'transfer.approve',
                // Les achats ne le concernent pas : ni fournisseurs, ni retours
                // fournisseurs, qui supposent de recevoir la marchandise.
                'supplier.view',
                'purchase.return',
            ])->pluck('id');

            $responsable->permissions()->detach($aRetirer);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transfers', 'requested_by')) {
            Schema::table('transfers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('requested_by');
                $table->dropConstrainedForeignId('approved_by');
                $table->dropColumn(['requested_at', 'approved_at', 'refusal_reason']);
            });
        }

        DB::table('transfer_statuses')->whereIn('code', ['requested', 'refused'])->delete();
        DB::table('permissions')->whereIn('name', ['transfer.request', 'transfer.approve'])->delete();
    }
};
