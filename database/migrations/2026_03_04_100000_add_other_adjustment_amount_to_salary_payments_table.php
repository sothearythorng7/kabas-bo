<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->decimal('other_adjustment_amount', 10, 2)->default(0)->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn('other_adjustment_amount');
        });
    }
};
