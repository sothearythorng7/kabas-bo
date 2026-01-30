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
            $table->string('pos_local_id')->nullable()->after('id');
            $table->unique(['shift_id', 'pos_local_id'], 'sales_shift_pos_local_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_shift_pos_local_unique');
            $table->dropColumn('pos_local_id');
        });
    }
};
