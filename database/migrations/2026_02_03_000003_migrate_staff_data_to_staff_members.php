<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create a StaffMember for each User with is_staff=true
        $staffUsers = DB::table('users')->where('is_staff', true)->get();

        foreach ($staffUsers as $user) {
            $staffMemberId = DB::table('staff_members')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'hire_date' => $user->hire_date,
                'store_id' => $user->store_id,
                'contract_status' => $user->contract_status ?? 'active',
                'contract_end_date' => $user->contract_end_date,
                'termination_reason' => $user->termination_reason,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update all 10 HR tables to set staff_member_id
            $hrTables = [
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

            foreach ($hrTables as $table) {
                DB::table($table)
                    ->where('user_id', $user->id)
                    ->update(['staff_member_id' => $staffMemberId]);
            }
        }
    }

    public function down(): void
    {
        // Truncate staff_members (cascading will clean up the FKs)
        DB::table('staff_members')->truncate();

        // Reset staff_member_id to null in all HR tables
        $hrTables = [
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

        foreach ($hrTables as $table) {
            DB::table($table)->update(['staff_member_id' => null]);
        }
    }
};
