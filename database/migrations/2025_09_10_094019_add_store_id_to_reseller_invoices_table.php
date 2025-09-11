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
            // Ajout de la colonne store_id (nullable)
            $table->unsignedBigInteger('store_id')->nullable()->after('reseller_id');

            // Si ta table stores existe déjà
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers_invoices', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
