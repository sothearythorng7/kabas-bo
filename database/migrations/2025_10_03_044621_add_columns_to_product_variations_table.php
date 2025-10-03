<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->foreignId('linked_product_id')->after('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variation_type_id')->after('linked_product_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_id', 'variation_type_id', 'variation_value_id', 'linked_product_id'], 'product_variation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropForeign(['linked_product_id']);
            $table->dropForeign(['variation_type_id']);
            $table->dropUnique('product_variation_unique');
            $table->dropColumn(['linked_product_id','variation_type_id']);
        });
    }
};
