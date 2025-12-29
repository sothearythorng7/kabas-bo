<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->foreignId('source_exchange_id')->nullable()->constrained('exchanges')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropForeign(['source_exchange_id']);
            $table->dropColumn('source_exchange_id');
        });
    }
};
