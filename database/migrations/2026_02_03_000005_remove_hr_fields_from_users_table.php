<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_staff',
                'phone',
                'address',
                'hire_date',
                'contract_status',
                'contract_end_date',
                'termination_reason',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->date('hire_date')->nullable()->after('address');
            $table->boolean('is_staff')->default(false)->after('remember_token');
            $table->string('contract_status')->default('active')->after('is_staff');
            $table->date('contract_end_date')->nullable()->after('contract_status');
            $table->string('termination_reason')->nullable()->after('contract_end_date');
        });
    }
};
