<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tables where user_id is a simple FK (no unique constraint involving user_id)
        $simpleTables = [
            'leaves',
            'user_documents',
            'salary_advances',
            'employee_commissions',
            'commission_calculations',
            'salary_adjustments',
            'user_salaries',
        ];

        foreach ($simpleTables as $table) {
            if (!Schema::hasColumn($table, 'user_id')) {
                continue; // Already processed
            }
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['user_id']);
                $t->dropColumn('user_id');
            });
        }

        // leave_quotas: has unique(['user_id', 'type', 'year']) — drop FK first, then unique, then column
        Schema::table('leave_quotas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('leave_quotas', function (Blueprint $table) {
            $table->dropUnique('leave_quotas_user_id_type_year_unique');
            $table->dropColumn('user_id');
            $table->unique(['staff_member_id', 'type', 'year']);
        });

        // user_schedules: has unique(['user_id', 'day_of_week']) — same treatment
        Schema::table('user_schedules', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('user_schedules', function (Blueprint $table) {
            $table->dropUnique('user_schedules_user_id_day_of_week_unique');
            $table->dropColumn('user_id');
            $table->unique(['staff_member_id', 'day_of_week']);
        });

        // salary_payments: has unique(['user_id', 'period']) — same treatment
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropUnique('salary_payments_user_id_period_unique');
            $table->dropColumn('user_id');
            $table->unique(['staff_member_id', 'period']);
        });
    }

    public function down(): void
    {
        // Re-add user_id to all tables
        $allTables = [
            'leaves',
            'user_documents',
            'salary_advances',
            'employee_commissions',
            'commission_calculations',
            'salary_adjustments',
            'user_salaries',
            'leave_quotas',
            'user_schedules',
            'salary_payments',
        ];

        foreach ($allTables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        // Restore unique constraints
        Schema::table('leave_quotas', function (Blueprint $table) {
            $table->dropUnique(['staff_member_id', 'type', 'year']);
            $table->unique(['user_id', 'type', 'year']);
        });

        Schema::table('user_schedules', function (Blueprint $table) {
            $table->dropUnique(['staff_member_id', 'day_of_week']);
            $table->unique(['user_id', 'day_of_week']);
        });

        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropUnique(['staff_member_id', 'period']);
            $table->unique(['user_id', 'period']);
        });
    }
};
