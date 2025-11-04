<?php

// database/migrations/2025_10_24_000000_fix_unique_index_on_product_variations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropUnique('product_variations_product_id_variation_value_id_unique');
            $table->unique(
                ['product_id', 'variation_type_id', 'variation_value_id'],
                'product_variations_unique_per_type_value'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropUnique('product_variations_unique_per_type_value');
            $table->unique(
                ['product_id', 'variation_value_id'],
                'product_variations_product_id_variation_value_id_unique'
            );
        });
    }
};
