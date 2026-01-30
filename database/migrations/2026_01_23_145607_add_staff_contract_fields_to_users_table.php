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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_staff')->default(false)->after('remember_token');
            $table->string('contract_status')->default('active')->after('is_staff'); // active, terminated
            $table->date('contract_end_date')->nullable()->after('contract_status');
            $table->string('termination_reason')->nullable()->after('contract_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_staff', 'contract_status', 'contract_end_date', 'termination_reason']);
        });
    }
};
