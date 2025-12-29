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
        // Add column to track items added via exchange
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('added_via_exchange_id')->nullable()->after('exchanged_in_exchange_id')
                ->constrained('exchanges')->nullOnDelete();
        });

        // Add columns to track exchange item types and new items
        Schema::table('exchange_items', function (Blueprint $table) {
            $table->string('type')->default('returned')->after('total_price'); // 'returned' or 'new'
            $table->foreignId('new_sale_item_id')->nullable()->after('original_sale_item_id')
                ->constrained('sale_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('added_via_exchange_id');
        });

        Schema::table('exchange_items', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropConstrainedForeignId('new_sale_item_id');
        });
    }
};
