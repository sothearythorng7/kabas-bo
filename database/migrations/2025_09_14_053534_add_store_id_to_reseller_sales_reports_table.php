<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStoreIdToResellerSalesReportsTable extends Migration
{
    public function up()
    {
        Schema::table('reseller_sales_reports', function (Blueprint $table) {
            // rendre reseller_id nullable (change() nécessite doctrine/dbal)
            $table->unsignedBigInteger('reseller_id')->nullable()->change();

            // ajouter store_id
            $table->unsignedBigInteger('store_id')->nullable()->after('reseller_id');

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('reseller_sales_reports', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');

            // remettre reseller_id non null si tu veux (attention aux données)
            // $table->unsignedBigInteger('reseller_id')->nullable(false)->change();
        });
    }
}
