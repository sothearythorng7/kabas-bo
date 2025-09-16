<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'waiting_reception', 'waiting_invoice', 'waiting_payment', 'paid'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'waiting_reception', 'waiting_invoice', 'waiting_payment', 'paid'])
                  ->default('pending')
                  ->change();
        });
    }
};
