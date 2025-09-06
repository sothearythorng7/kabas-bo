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
        Schema::create('warehouse_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('creditor_name'); // Nom du créancier
            $table->text('description')->nullable();
            $table->enum('type', ['general_expense', 'supply_purchase', 'product_purchase', 'raw_material_purchase']);
            $table->enum('status', ['to_pay', 'paid', 'reimbursed', 'cancelled'])->default('to_pay');
            $table->json('status_history')->nullable(); // historisation des statuts
            $table->string('creditor_invoice_number')->nullable();
            $table->decimal('amount_usd', 12, 2)->nullable();
            $table->decimal('amount_riel', 12, 2)->nullable();
            $table->string('internal_payment_number')->nullable();
            $table->enum('payment_type', ['bank_transfer', 'cash'])->nullable();
            $table->string('attachment_path')->nullable(); // image ou pdf
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_invoices');
    }
};
