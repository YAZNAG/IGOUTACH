<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventes : documents (devis / facture), lignes, grand-livre client,
 * encaissements et sessions de caisse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->decimal('opening_amount', 12, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_amount', 12, 2)->nullable();
            $table->decimal('expected_amount', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->string('status', 10)->default('open'); // open | closed
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type', 10)->default('invoice'); // quote | invoice
            // draft → confirmed (stock sorti, créance créée) → cancelled
            $table->string('status', 20)->default('draft');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_status', 10)->default('unpaid'); // unpaid | partial | paid
            $table->timestamp('confirmed_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['warehouse_id', 'confirmed_at']);
        });

        Schema::create('sale_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->string('price_type_code', 20)->nullable();
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('customer_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            // invoice (+) | payment (−) | return (−) | adjustment (±)
            $table->string('type', 15);
            $table->decimal('amount', 12, 2); // signé : + augmente l'encours
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            // Cycle du chèque : received → deposited → cleared | bounced
            $table->string('cheque_status', 12)->nullable();
            $table->string('cheque_reference')->nullable();
            $table->date('received_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cash_sessions');
    }
};
