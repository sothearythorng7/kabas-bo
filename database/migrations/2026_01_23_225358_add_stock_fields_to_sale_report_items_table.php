<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_report_items', function (Blueprint $table) {
            $table->integer('old_stock')->default(0)->after('product_id');
            $table->integer('refill')->default(0)->after('old_stock');
            $table->integer('stock_on_hand')->default(0)->after('refill');
            $table->decimal('selling_price', 12, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_report_items', function (Blueprint $table) {
            $table->dropColumn(['old_stock', 'refill', 'stock_on_hand', 'selling_price']);
        });
    }
};
