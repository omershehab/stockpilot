<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number', 100);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'batch_number']);
            $table->index(['company_id', 'expiry_date']);
        });

        DB::table('products')
            ->select(['id', 'company_id', 'batch_number', 'expiry_date'])
            ->where(function ($query) {
                $query->whereNotNull('batch_number')->orWhereNotNull('expiry_date');
            })
            ->orderBy('id')
            ->chunkById(500, function ($products) {
                $now = now();
                DB::table('product_batches')->insert($products->map(fn ($product) => [
                    'company_id' => $product->company_id,
                    'product_id' => $product->id,
                    'batch_number' => $product->batch_number ?: 'LEGACY-'.$product->id,
                    'expiry_date' => $product->expiry_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('batch_number', 100)->nullable()->after('image_path');
            $table->date('expiry_date')->nullable()->after('batch_number');
        });

        DB::table('product_batches')->orderBy('id')->get()->groupBy('product_id')->each(function ($batches, $productId) {
            $batch = $batches->first();
            DB::table('products')->where('id', $productId)->update([
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
            ]);
        });

        Schema::dropIfExists('product_batches');
    }
};
