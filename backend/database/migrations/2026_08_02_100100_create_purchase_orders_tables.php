<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Achats : bons de commande + lignes.
 *
 * Aucun champ de prix sur les lignes ni l'en-tête.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique(); // BC-YYYY-0001
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->timestamp('ordered_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->foreignId('status_id')->constrained('purchase_order_statuses')->restrictOnDelete();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'status_id']);
            $table->index(['warehouse_id', 'status_id']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['purchase_order_id', 'product_id']);
            $table->index(['purchase_order_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
