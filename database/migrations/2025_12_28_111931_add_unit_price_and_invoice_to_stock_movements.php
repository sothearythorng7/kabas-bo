<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter unit_price aux items de mouvement
        Schema::table('stock_movement_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
        });

        // Ajouter les champs de facturation aux mouvements de stock
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->nullable()->after('status');
            $table->string('invoice_number')->nullable()->after('total_amount');
            $table->string('invoice_path')->nullable()->after('invoice_number');
            $table->foreignId('from_transaction_id')->nullable()->after('invoice_path')
                ->constrained('financial_transactions')->nullOnDelete();
            $table->foreignId('to_transaction_id')->nullable()->after('from_transaction_id')
                ->constrained('financial_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movement_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['from_transaction_id']);
            $table->dropForeign(['to_transaction_id']);
            $table->dropColumn(['total_amount', 'invoice_number', 'invoice_path', 'from_transaction_id', 'to_transaction_id']);
        });
    }
};
