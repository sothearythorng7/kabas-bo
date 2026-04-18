<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('paid_at');
        });

        // Backfill: use paid_at as invoice_date for existing orders that have been paid
        DB::table('supplier_orders')
            ->whereNotNull('paid_at')
            ->whereNull('invoice_date')
            ->update(['invoice_date' => DB::raw('paid_at')]);
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn('invoice_date');
        });
    }
};
