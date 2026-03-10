<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_sales_report_anomalies', function (Blueprint $table) {
            $table->unsignedInteger('reported_quantity')->nullable()->after('quantity');
            $table->unsignedInteger('accepted_quantity')->nullable()->after('reported_quantity');
            $table->string('status', 20)->default('pending')->after('description');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            $table->text('resolution_note')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_sales_report_anomalies', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn([
                'reported_quantity',
                'accepted_quantity',
                'status',
                'resolved_by',
                'resolved_at',
                'resolution_note',
            ]);
        });
    }
};
