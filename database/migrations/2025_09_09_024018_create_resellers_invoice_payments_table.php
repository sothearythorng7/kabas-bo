<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resellers_invoice_payments', function (Blueprint $table) {
            $table->id();

            // FK vers la table resellers_invoices
            $table->foreignId('resellers_invoice_id')
                  ->constrained('resellers_invoices')
                  ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at')->useCurrent(); // timestamp du paiement
            // champs optionnels utiles
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();

            $table->timestamps();

            $table->index('resellers_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers_invoice_payments');
    }
};
