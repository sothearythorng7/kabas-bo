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
        Schema::table('sale_items', function (Blueprint $table) {
            // Type d'article: product, gift_box, gift_card
            $table->string('item_type')->default('product')->after('product_id');

            // ID du coffret cadeau (si type = gift_box)
            $table->foreignId('gift_box_id')->nullable()->after('item_type')
                ->constrained('gift_boxes')->nullOnDelete();

            // ID de la carte cadeau (si type = gift_card)
            $table->foreignId('gift_card_id')->nullable()->after('gift_box_id')
                ->constrained('gift_cards')->nullOnDelete();

            // Code généré pour la carte cadeau vendue
            $table->foreignId('generated_gift_card_code_id')->nullable()->after('gift_card_id')
                ->constrained('gift_card_codes')->nullOnDelete();

            // Rendre product_id nullable (il peut être null pour gift_box/gift_card)
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['gift_box_id']);
            $table->dropForeign(['gift_card_id']);
            $table->dropForeign(['generated_gift_card_code_id']);
            $table->dropColumn(['item_type', 'gift_box_id', 'gift_card_id', 'generated_gift_card_code_id']);
        });
    }
};
