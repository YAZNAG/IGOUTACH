<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portefeuille de chèques.
 *
 * Un même chèque peut vivre deux vies : reçu d'un client, puis endossé au
 * profit d'un fournisseur. Il est donc suivi comme une entité à part entière
 * plutôt que comme deux champs figés sur un règlement — sans quoi il serait
 * impossible de savoir quel chèque encaissé a servi à payer quel fournisseur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cheques')) {
            Schema::create('cheques', function (Blueprint $table): void {
                $table->id();

                $table->string('number', 50);                 // série / numéro
                $table->date('cheque_date');                  // date portée sur le chèque
                $table->decimal('amount', 12, 2);
                $table->string('bank', 100)->nullable();

                // 'in'  : encaissé — reçu d'un client
                // 'out' : décaissé — remis à un fournisseur
                $table->string('direction', 3);

                // 'customer'    : le client a signé le chèque
                // 'own'         : notre propre chéquier
                // 'third_party' : chèque signé par un tiers
                $table->string('origin', 15);

                // Nom porté sur le chèque. Renseigné dès qu'il diffère du
                // client : c'est le nom du tireur qui engage la banque, pas
                // celui de la personne qui remet le chèque.
                $table->string('drawer_name')->nullable();

                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

                $table->string('image_path')->nullable();

                // 'portfolio'   : en portefeuille, disponible
                // 'handed_over' : remis à un fournisseur (endossé)
                // 'cashed'      : encaissé en banque
                // 'bounced'     : rejeté
                $table->string('status', 15)->default('portfolio');

                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['direction', 'status']);
                $table->index('customer_id');
                $table->index('supplier_id');
                $table->index('cheque_date');
                // Un numéro est unique pour une banque donnée : le doublon
                // signale une saisie en double, pas un second chèque.
                $table->unique(['number', 'bank']);
            });
        }

        // Rattachement aux règlements existants, sans casser l'historique.
        foreach (['payments', 'supplier_payments'] as $table) {
            if (! Schema::hasColumn($table, 'cheque_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->foreignId('cheque_id')->nullable()->after('payment_method_id')
                        ->constrained('cheques')->nullOnDelete();
                });
            }
        }

        $this->seedPermissions();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'cheque.view' => ['module' => 'payments', 'label' => 'Consulter le portefeuille de chèques', 'herite_de' => 'payment.view'],
            'cheque.manage' => ['module' => 'payments', 'label' => 'Enregistrer et endosser des chèques', 'herite_de' => 'payment.create'],
        ];

        foreach ($permissions as $name => $meta) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $meta['label'], 'module' => $meta['module']],
            );

            // Les rôles disposant déjà de l'équivalent héritent : personne ne
            // perd un accès qu'il exerçait avant cette migration.
            $source = Permission::where('name', $meta['herite_de'])->first();

            if ($source !== null) {
                foreach (DB::table('role_permission')->where('permission_id', $source->id)->pluck('role_id') as $roleId) {
                    Role::find($roleId)?->permissions()->syncWithoutDetaching([$permission->id]);
                }
            }
        }

        Role::where('name', 'admin')->first()?->permissions()->sync(Permission::pluck('id')->all());
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cheque_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cheque_id');
        });

        Schema::dropIfExists('cheques');

        DB::table('permissions')->whereIn('name', ['cheque.view', 'cheque.manage'])->delete();
    }
};
