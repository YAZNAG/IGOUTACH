<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fiche technique : attributs libres par article + modèles d'attributs par
 * catégorie (pré-remplissage des noms lors de la saisie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'name']);
        });

        Schema::create('product_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->string('value');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('attribute_templates');
    }
};
