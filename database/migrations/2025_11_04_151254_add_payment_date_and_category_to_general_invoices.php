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
        Schema::table('general_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('general_invoices', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('general_invoices', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('account_id')->constrained('invoice_categories')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['payment_date', 'category_id']);
        });
    }
};
