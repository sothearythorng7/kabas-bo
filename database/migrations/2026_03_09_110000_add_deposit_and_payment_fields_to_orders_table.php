<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('total');
            $table->boolean('deposit_paid')->default(false)->after('deposit_amount');
            $table->string('payment_type', 30)->nullable()->after('payment_method');
            // payment_type: 'payment_link', 'cash', 'bank_transfer' — null for website orders
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'deposit_paid', 'payment_type']);
        });
    }
};
