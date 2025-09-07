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
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_order_id')->nullable()->constrained()->nullOnDelete();

            // Données du lot
            $table->decimal('purchase_price', 10, 2);
            $table->integer('quantity');            // quantité reçue initialement
            $table->integer('quantity_remaining');  // quantité encore en stock

            // Infos optionnelles
            $table->string('batch_number')->nullable();  // n° de lot / série
            $table->date('expiry_date')->nullable();     // DLC / DLUO si besoin

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
