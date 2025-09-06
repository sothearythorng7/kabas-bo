<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_store', function (Blueprint $table) {
            $table->integer('alert_stock_quantity')->default(0)->after('stock_quantity');
        });
    }

    public function down()
    {
        Schema::table('product_store', function (Blueprint $table) {
            $table->dropColumn('alert_stock_quantity');
        });
}

};
