<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('product_limit')->nullable()->default(1000)->after('plan');
            $table->unsignedInteger('user_limit')->nullable()->default(10)->after('product_limit');
            $table->unsignedInteger('warehouse_limit')->nullable()->default(3)->after('user_limit');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['product_limit', 'user_limit', 'warehouse_limit']);
        });
    }
};
