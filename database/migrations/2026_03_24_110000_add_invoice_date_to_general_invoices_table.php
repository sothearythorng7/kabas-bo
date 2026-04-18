<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('due_date');
        });

        // Backfill: copier due_date dans invoice_date pour les factures existantes
        DB::table('general_invoices')
            ->whereNotNull('due_date')
            ->whereNull('invoice_date')
            ->update(['invoice_date' => DB::raw('due_date')]);
    }

    public function down(): void
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_date');
        });
    }
};
