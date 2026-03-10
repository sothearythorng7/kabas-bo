<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('merchant_ref_no', 50)->nullable()->after('tran_id');
            $table->index('merchant_ref_no');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['merchant_ref_no']);
            $table->dropColumn('merchant_ref_no');
        });
    }
};
