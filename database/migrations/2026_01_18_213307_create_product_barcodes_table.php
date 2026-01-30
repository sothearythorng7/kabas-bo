<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Créer la table product_barcodes
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('barcode')->unique();
            $table->string('type')->default('ean13'); // ean13, ean8, upc, internal
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_primary']);
        });

        // 2. Migrer les EAN existants depuis la table products
        $products = DB::table('products')
            ->whereNotNull('ean')
            ->where('ean', '!=', '')
            ->select('id', 'ean')
            ->get();

        foreach ($products as $product) {
            DB::table('product_barcodes')->insert([
                'product_id' => $product->id,
                'barcode' => $product->ean,
                'type' => 'ean13',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
