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
        Schema::create('reseller_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Un seul prix par couple reseller/product
            $table->unique(['reseller_id', 'product_id']);
        });

        // Initialiser avec les prix B2B existants pour tous les revendeurs et produits revendables
        $resellers = DB::table('resellers')->pluck('id');
        $products = DB::table('products')
            ->where('is_resalable', true)
            ->select('id', 'price', 'price_btob')
            ->get();

        $now = now();
        $inserts = [];

        foreach ($resellers as $resellerId) {
            foreach ($products as $product) {
                $inserts[] = [
                    'reseller_id' => $resellerId,
                    'product_id' => $product->id,
                    'price' => $product->price_btob ?? $product->price,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insérer par lots de 500 pour éviter les problèmes de mémoire
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('reseller_product_prices')->insert($chunk);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_product_prices');
    }
};
