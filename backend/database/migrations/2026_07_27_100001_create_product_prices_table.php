<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('price_type_id')->constrained('price_types')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('min_quantity')->nullable();      // surcharge le seuil du type
            $table->decimal('min_margin_percent', 5, 2)->default(0);  // plancher propre au niveau
            // Précision microseconde : autorise plusieurs versions dans la même seconde.
            $table->timestamp('valid_from', 6)->useCurrent();
            $table->timestamp('valid_to', 6)->nullable();             // NULL = en vigueur
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'price_type_id', 'valid_from']);
            $table->index(['product_id', 'price_type_id', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
