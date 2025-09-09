<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resellers_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reseller_stock_delivery_id')
                  ->constrained('reseller_stock_deliveries')
                  ->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers_invoice_payments');
        Schema::dropIfExists('resellers_invoices');
    }
};
