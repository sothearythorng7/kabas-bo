<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->boolean('is_transferred')->default(false)->after('financial_transaction_id');
            $table->timestamp('transferred_at')->nullable()->after('is_transferred');
            $table->string('transfer_reference', 255)->nullable()->after('transferred_at');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn(['is_transferred', 'transferred_at', 'transfer_reference']);
        });
    }
};
