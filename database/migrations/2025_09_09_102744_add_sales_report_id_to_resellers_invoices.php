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
        Schema::table('resellers_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_report_id')->nullable()->after('reseller_stock_delivery_id');
            
            $table->foreign('sales_report_id')
                  ->references('id')
                  ->on('reseller_sales_reports')
                  ->onDelete('set null'); // si le report est supprimé, on garde la facture mais dissociée
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_report_id']);
            $table->dropColumn('sales_report_id');
        });
    }
};
