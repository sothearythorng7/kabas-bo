<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->decimal('overtime_amount', 10, 2)->default(0)->after('advances_deduction');
            $table->decimal('bonus_amount', 10, 2)->default(0)->after('overtime_amount');
            $table->decimal('penalty_amount', 10, 2)->default(0)->after('bonus_amount');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('penalty_amount');
            $table->decimal('gross_salary', 10, 2)->nullable()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_amount',
                'bonus_amount',
                'penalty_amount',
                'commission_amount',
                'gross_salary',
            ]);
        });
    }
};
