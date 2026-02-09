<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->boolean('start_half_day')->default(false)->after('end_date');
            $table->boolean('end_half_day')->default(false)->after('start_half_day');
            $table->foreignId('leave_quota_id')->nullable()->after('end_half_day')
                ->constrained('leave_quotas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['leave_quota_id']);
            $table->dropColumn(['start_half_day', 'end_half_day', 'leave_quota_id']);
        });
    }
};
