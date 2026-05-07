<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_items', 'source_promotion_id')) {
                $table->foreignId('source_promotion_id')
                    ->nullable()
                    ->after('special_order_id')
                    ->constrained('promotion_rules')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('cart_items', 'source_item_id')) {
                $table->unsignedBigInteger('source_item_id')
                    ->nullable()
                    ->after('source_promotion_id');
                $table->foreign('source_item_id')
                    ->references('id')
                    ->on('cart_items')
                    ->nullOnDelete();
                $table->index('source_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'source_item_id')) {
                $table->dropForeign(['source_item_id']);
                $table->dropIndex(['source_item_id']);
                $table->dropColumn('source_item_id');
            }
            if (Schema::hasColumn('cart_items', 'source_promotion_id')) {
                $table->dropForeign(['source_promotion_id']);
                $table->dropColumn('source_promotion_id');
            }
        });
    }
};
