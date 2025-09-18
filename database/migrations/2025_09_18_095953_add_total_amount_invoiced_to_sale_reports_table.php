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
        Schema::table('sale_reports', function (Blueprint $table) {
            $table->decimal('total_amount_invoiced', 12, 2)->default(0)->after('total_amount_theoretical');
        });
    }

    public function down(): void
    {
        Schema::table('sale_reports', function (Blueprint $table) {
            $table->dropColumn('total_amount_invoiced');
        });
    }

};
