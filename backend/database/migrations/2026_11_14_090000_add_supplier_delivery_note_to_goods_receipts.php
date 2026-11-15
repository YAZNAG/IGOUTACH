<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bon de livraison du fournisseur joint à la réception : le papier (ou la
 * photo du papier) qui accompagne la marchandise, gardé avec le BR pour
 * pouvoir le ressortir en cas de litige sur les quantités ou les prix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            // Chemin sur le disque privé : le document d'un fournisseur ne se
            // sert pas en accès public, il passe par une route authentifiée.
            $table->string('supplier_note_path')->nullable()->after('notes');
            $table->string('supplier_note_name')->nullable()->after('supplier_note_path');
            $table->string('supplier_note_mime', 100)->nullable()->after('supplier_note_name');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropColumn(['supplier_note_path', 'supplier_note_name', 'supplier_note_mime']);
        });
    }
};
