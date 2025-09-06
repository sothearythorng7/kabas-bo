<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_invoice_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_invoice_id')
                  ->constrained('warehouse_invoices')
                  ->onDelete('cascade');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_invoice_files');
    }
};
