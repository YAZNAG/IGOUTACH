<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // 'depot', 'pos', 'vehicle'
            $table->string('name');
            $table->boolean('allows_sales')->default(false);
            $table->boolean('allows_purchase_receipt')->default(false);
            $table->boolean('requires_transfer_approval')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_types');
    }
};
