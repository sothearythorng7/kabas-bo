<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->timestamp('exchanged_at')->nullable();
            $table->foreignId('exchanged_in_exchange_id')->nullable()->constrained('exchanges')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['exchanged_in_exchange_id']);
            $table->dropColumn(['exchanged_at', 'exchanged_in_exchange_id']);
        });
    }
};
