<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->foreignId('source_production_id')->nullable()->after('source_refill_id')->constrained('productions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_production_id');
        });
    }
};
