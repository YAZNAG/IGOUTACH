<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Propositions d'images faites par l'agent pour les articles sans visuel :
 * les candidates trouvées, puis la décision de l'administrateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_image_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // La requête lancée au moteur d'images.
            $table->string('query', 200);
            // Candidates : [{url, thumbnail, title, source, width, height}].
            $table->json('candidates');
            // pending | approved | rejected
            $table->string('status', 12)->default('pending');
            $table->unsignedSmallInteger('chosen_index')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'updated_at']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image_proposals');
    }
};
