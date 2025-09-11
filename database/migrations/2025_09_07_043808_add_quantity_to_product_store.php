<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_store', function (Blueprint $table) {
            if (!Schema::hasColumn('product_store', 'alert_stock_quantity')) {
                $table->integer('alert_stock_quantity')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_store', function (Blueprint $table) {
            $table->dropColumn(['alert_stock_quantity']);
        });
    }
};
