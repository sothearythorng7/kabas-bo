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
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['has_delivery', 'delivery_fee', 'delivery_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('has_delivery')->default(false)->after('split_payments');
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('has_delivery');
            $table->text('delivery_address')->nullable()->after('delivery_fee');
        });
    }
};
