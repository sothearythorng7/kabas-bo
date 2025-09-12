<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->foreignId('reseller_invoice_id')->nullable()->constrained('resellers_invoices')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('direction', ['debit', 'credit']);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'validated', 'canceled', 'refunded'])->default('draft');
            $table->dateTime('transaction_date');
            $table->foreignId('payment_method_id')->constrained('financial_payment_methods');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('external_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
