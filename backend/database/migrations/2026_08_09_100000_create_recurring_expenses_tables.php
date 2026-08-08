<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Charges fixes : loyer, abonnements, salaires…
 *
 * La charge est saisie une seule fois ; une échéance est générée chaque mois
 * et reste en alerte tant qu'elle n'est pas réglée. Les échéances sont des
 * lignes à part entière plutôt qu'un calcul à la volée : sans elles,
 * impossible de savoir quel mois a été payé, quand, et par quel moyen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table): void {
            $table->id();

            $table->string('label');
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            // Nul = charge de la société, non imputée à un lieu précis.
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->decimal('amount', 12, 2);

            // Jour d'échéance. 31 vaut « fin de mois » : la date est ramenée
            // au dernier jour pour les mois plus courts.
            $table->unsignedTinyInteger('day_of_month')->default(1);

            $table->string('start_period', 7);              // AAAA-MM
            $table->string('end_period', 7)->nullable();    // nul = sans fin

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'start_period']);
        });

        Schema::create('recurring_expense_occurrences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('recurring_expense_id')->constrained('recurring_expenses')->cascadeOnDelete();
            $table->string('period', 7);                    // AAAA-MM
            $table->date('due_date');
            $table->decimal('amount', 12, 2);

            // 'pending' : due, non réglée — reste en alerte
            // 'paid'    : réglée
            // 'skipped' : volontairement passée (mois non dû)
            $table->string('status', 10)->default('pending');

            $table->date('paid_at')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            // Charge réelle créée au règlement : l'échéance rejoint la
            // comptabilité des charges au lieu de vivre à côté.
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            // Une seule échéance par charge et par mois : la génération peut
            // être rejouée sans créer de doublon.
            $table->unique(['recurring_expense_id', 'period']);
            $table->index(['status', 'due_date']);
        });

        // Le règlement d'une charge doit dire par quel moyen il a été fait.
        if (! Schema::hasColumn('expenses', 'payment_method_id')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->foreignId('payment_method_id')->nullable()->after('amount')
                    ->constrained('payment_methods')->nullOnDelete();
            });
        }

        $this->seedPermissions();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'expense.recurring_manage' => [
                'module' => 'expenses',
                'label' => 'Gérer les charges fixes',
                'herite_de' => 'expense.approve',
            ],
        ];

        foreach ($permissions as $name => $meta) {
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
    }

    public function down(): void
    {
        if (Schema::hasColumn('expenses', 'payment_method_id')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('payment_method_id');
            });
        }

        Schema::dropIfExists('recurring_expense_occurrences');
        Schema::dropIfExists('recurring_expenses');

        DB::table('permissions')->where('name', 'expense.recurring_manage')->delete();
    }
};
