<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_time_min')->nullable()->after('price');
            $table->unsignedSmallInteger('delivery_time_max')->nullable()->after('delivery_time_min');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropColumn(['delivery_time_min', 'delivery_time_max']);
        });
    }
};
