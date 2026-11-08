<?php

declare(strict_types=1);

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Boutique en ligne (store.igoutech.optizaworks.com) : commandes passées par
 * les clients du site, leurs lignes, leurs règlements, les réglages de la
 * boutique, et la visibilité en ligne des articles. Le catalogue, les
 * prix, les images et le stock restent ceux de la gestion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Visible sur la boutique ; mis en avant sur la page d'accueil.
            $table->boolean('store_visible')->default(true)->after('is_active');
            $table->boolean('store_featured')->default(false)->after('store_visible');
            $table->text('store_description')->nullable()->after('description');
        });

        Schema::create('store_settings', function (Blueprint $table): void {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('store_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 20)->unique();
            // new → confirmed → preparing → shipped → delivered ; cancelled
            $table->string('status', 15)->default('new');
            $table->string('customer_name', 120);
            $table->string('customer_phone', 30);
            $table->string('customer_email', 190)->nullable();
            $table->string('city', 120);
            $table->string('address', 255);
            $table->string('note', 500)->nullable();
            // cod (à la livraison) | transfer (virement) | card (plus tard)
            $table->string('payment_method', 12)->default('cod');
            // unpaid | partial | paid | refunded
            $table->string('payment_status', 10)->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            // Lieu qui prépare et expédie ; la vente de gestion créée à la livraison.
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('customer_phone');
        });

        Schema::create('store_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // Le nom et le prix au moment de la commande : le catalogue bouge.
            $table->string('name', 191);
            $table->string('sku', 60)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('store_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->string('method', 12);
            $table->decimal('amount', 12, 2);
            $table->string('reference', 120)->nullable();
            $table->date('received_at');
            $table->string('note', 255)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $defauts = [
            'shop_name' => 'iGouTech Store',
            'phone' => '0661341783',
            'whatsapp' => '212661341783',
            'email' => 'igoutechsarl@gmail.com',
            'address' => 'N 82 bis Hay El Ghayaten, Av Essaadyine, Dcheira El Jihadia',
            'shipping_fee' => '30',
            'free_shipping_from' => '500',
            'payment_cod' => '1',
            'payment_transfer' => '1',
            'bank_details' => '',
            'announcement' => 'Livraison partout au Maroc · Paiement à la livraison',
            'hero_title' => 'Électronique, satellite et câblage au meilleur prix',
            'hero_subtitle' => 'Récepteurs, télécommandes, câbles réseau, paraboles et accessoires, livrés chez vous.',
        ];
        foreach ($defauts as $k => $v) {
            DB::table('store_settings')->updateOrInsert(['key' => $k], ['value' => $v, 'created_at' => now(), 'updated_at' => now()]);
        }

        // La permission de la boutique, donnée aux rôles qui gèrent déjà les
        // paramètres : la direction, sans rien retirer à personne.
        $permission = Permission::firstOrCreate(
            ['name' => 'store.manage'],
            ['display_name' => 'Gérer la boutique en ligne (commandes, paiements, réglages)', 'module' => 'store'],
        );
        $source = Permission::where('name', 'settings.manage')->first();
        foreach ($source?->roles()->pluck('roles.id')->all() ?? [] as $roleId) {
            Role::find($roleId)?->permissions()->syncWithoutDetaching([$permission->id]);
        }
        Cache::flush();
    }

    public function down(): void
    {
        Schema::dropIfExists('store_payments');
        Schema::dropIfExists('store_order_lines');
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('store_settings');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['store_visible', 'store_featured', 'store_description']);
        });
        Permission::where('name', 'store.manage')->delete();
    }
};
