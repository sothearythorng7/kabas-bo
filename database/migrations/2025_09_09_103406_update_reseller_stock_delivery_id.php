<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_stock_delivery_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('resellers_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_stock_delivery_id')->nullable(false)->change();
        });
    }
};
