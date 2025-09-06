<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_invoice_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_invoice_id')
                  ->constrained('warehouse_invoices')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('status')->nullable(); // statut après modification
            $table->json('changes')->nullable();   // détails des modifications
            $table->timestamps();                  // created_at = date de modification
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_invoice_histories');
    }
};
