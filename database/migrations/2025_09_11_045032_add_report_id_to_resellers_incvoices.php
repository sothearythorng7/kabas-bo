<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers_invoices', function (Blueprint $table) {
            // Ajoute la colonne pour lier une facture à un rapport de ventes
            $table->unsignedBigInteger('sales_report_id')->nullable()->after('reseller_stock_delivery_id');

            // Optionnel : ajoute une clé étrangère si tu veux lier à la table des rapports
            $table->foreign('sales_report_id')
                  ->references('id')
                  ->on('reseller_sales_reports')
                  ->onDelete('set null'); // ou 'cascade' selon le besoin
        });
    }

    public function down(): void
    {
        Schema::table('resellers_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_report_id']);
            $table->dropColumn('sales_report_id');
        });
    }
};
