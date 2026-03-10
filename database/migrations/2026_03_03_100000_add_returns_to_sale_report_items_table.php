<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_report_items', function (Blueprint $table) {
            $table->integer('returns')->default(0)->after('refill');
        });
    }

    public function down(): void
    {
        Schema::table('sale_report_items', function (Blueprint $table) {
            $table->dropColumn('returns');
        });
    }
};
