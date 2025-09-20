<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sale_reports', function (Blueprint $table) {
            $table->string('report_file_path')->nullable()->after('is_paid');
            $table->timestamp('sent_at')->nullable()->after('report_file_path');
            $table->text('sent_to')->nullable()->after('sent_at');
        });
    }

    public function down(): void {
        Schema::table('sale_reports', function (Blueprint $table) {
            $table->dropColumn(['report_file_path', 'sent_at', 'sent_to']);
        });
    }
};
