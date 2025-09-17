<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('account_id')
                ->constrained('financial_accounts')
                ->cascadeOnDelete();
            $table->string('label');
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('attachment'); // chemin du fichier PDF / image
            $table->text('note')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_invoices');
    }
};
