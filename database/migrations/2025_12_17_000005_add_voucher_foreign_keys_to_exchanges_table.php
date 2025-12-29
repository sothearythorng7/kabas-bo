<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->foreign('payment_voucher_id')->references('id')->on('vouchers')->nullOnDelete();
            $table->foreign('generated_voucher_id')->references('id')->on('vouchers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropForeign(['payment_voucher_id']);
            $table->dropForeign(['generated_voucher_id']);
        });
    }
};
