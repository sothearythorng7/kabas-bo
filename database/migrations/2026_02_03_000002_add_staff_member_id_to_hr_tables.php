<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'leaves',
            'leave_quotas',
            'user_salaries',
            'user_documents',
            'user_schedules',
            'salary_advances',
            'salary_payments',
            'employee_commissions',
            'commission_calculations',
            'salary_adjustments',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('staff_member_id')->nullable()->after('id')
                    ->constrained('staff_members')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'leaves',
            'leave_quotas',
            'user_salaries',
            'user_documents',
            'user_schedules',
            'salary_advances',
            'salary_payments',
            'employee_commissions',
            'commission_calculations',
            'salary_adjustments',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign([$table === 'staff_members' ? '' : 'staff_member_id']);
                $t->dropColumn('staff_member_id');
            });
        }
    }
};
