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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_custom_service')->default(false)->after('delivery_address');
            $table->text('custom_service_description')->nullable()->after('is_custom_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_custom_service', 'custom_service_description']);
        });
    }
};
