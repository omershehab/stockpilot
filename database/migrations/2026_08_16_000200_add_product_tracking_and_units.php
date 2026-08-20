<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('name_ar', 80)->nullable();
            $table->string('symbol', 30);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'symbol']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('batch_number', 100)->nullable()->after('image_path');
            $table->date('expiry_date')->nullable()->after('batch_number');
        });

        $now = now();
        $defaults = [
            ['name' => 'Pieces', 'name_ar' => 'قطعة', 'symbol' => 'pcs'],
            ['name' => 'Box', 'name_ar' => 'صندوق', 'symbol' => 'box'],
            ['name' => 'Kilogram', 'name_ar' => 'كجم', 'symbol' => 'kg'],
            ['name' => 'Meter', 'name_ar' => 'متر', 'symbol' => 'm'],
            ['name' => 'Liter', 'name_ar' => 'لتر', 'symbol' => 'liter'],
        ];

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('units')->insert(array_map(fn (array $unit) => [
                ...$unit,
                'company_id' => $companyId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $defaults));
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiry_date']);
        });

        Schema::dropIfExists('units');
    }
};
