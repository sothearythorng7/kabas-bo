<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corriger les colonnes quantity pour supporter les décimales
     */
    public function up(): void
    {
        Schema::table('supplier_order_raw_material', function (Blueprint $table) {
            $table->decimal('quantity_ordered', 12, 4)->default(0)->change();
            $table->decimal('quantity_received', 12, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_order_raw_material', function (Blueprint $table) {
            $table->integer('quantity_ordered')->default(0)->change();
            $table->integer('quantity_received')->nullable()->change();
        });
    }
};
