<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // code nullable-unique : plusieurs NULL tolérés le temps du backfill.
            $table->string('code')->nullable()->unique()->after('id');
            $table->string('logo_path')->nullable()->after('name');
            $table->string('website')->nullable()->after('logo_path');
            $table->unsignedInteger('position')->default(0)->after('website');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['code', 'logo_path', 'website', 'position']);
        });
    }
};
