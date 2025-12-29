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
        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('cash_in', 10, 2)->default(0)->after('cash_difference');
            $table->decimal('cash_out', 10, 2)->default(0)->after('cash_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['cash_in', 'cash_out']);
        });
    }
};
