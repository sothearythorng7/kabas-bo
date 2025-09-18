<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sale_reports', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('status');
        });
    }

    public function down(): void {
        Schema::table('sale_reports', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
};

