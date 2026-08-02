<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            // Paiement fournisseur à la réception : payé, partiel (reste = crédit
            // fournisseur à régler plus tard) ou non payé (tout en crédit).
            $table->string('payment_status', 20)->default('unpaid')->after('invoice_number');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'amount_paid']);
        });
    }
};
