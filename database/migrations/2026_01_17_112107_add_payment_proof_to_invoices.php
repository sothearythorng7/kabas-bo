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
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('payment_date');
        });

        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->date('paid_at')->nullable()->after('is_paid');
            $table->string('payment_proof')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });

        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payment_proof']);
        });
    }
};
