<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clients : type de prix par défaut, vendeur référent et lieu de rattachement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('price_type_id')->nullable()->after('ice')
                ->constrained('price_types')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->after('price_type_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('seller_id')
                ->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('seller_id');
            $table->dropConstrainedForeignId('price_type_id');
        });
    }
};
