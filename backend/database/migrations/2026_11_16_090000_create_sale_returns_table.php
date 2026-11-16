<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retours clients rattachés à la vente d'origine.
 *
 * Un retour « en l'air » remet du stock sans dire d'où il vient : le
 * bénéfice continue de compter la vente entière, et personne ne sait à quel
 * prix l'article avait été vendu. Rattaché à sa ligne de vente, le retour
 * reprend le prix de cette vente-là et vient en déduction du bénéfice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('sale_line_id')->constrained('sale_lines')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedInteger('quantity');
            // Le prix de la vente d'origine, figé : c'est lui qu'on rembourse
            // et qu'on retire du chiffre d'affaires.
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            // Part de l'avoir imputée sur ce que la facture devait encore ;
            // le reste a été rendu au client de la main à la main.
            $table->decimal('credited_amount', 12, 2)->default(0);
            $table->string('condition', 15); // resellable | defective
            $table->string('note')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('sale_line_id');
            $table->index(['product_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
