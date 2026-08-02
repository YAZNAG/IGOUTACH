<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Achats : bons de réception + lignes.
 *
 * Le prix d'achat réel est saisi ici (et seulement ici) : il alimente le CMUP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique(); // BR-YYYY-0001
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->dateTime('received_at'); // date effective du mouvement de stock
            $table->string('invoice_number')->nullable(); // n° facture / BL fournisseur
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'received_at']);
            $table->index(['warehouse_id', 'received_at']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->string('over_receipt_reason')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['goods_receipt_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
    }
};
