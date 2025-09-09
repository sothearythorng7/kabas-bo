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
        Schema::create('reseller_stock_delivery_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_stock_delivery_id')
                ->constrained('reseller_stock_deliveries')
                ->onDelete('cascade')
                ->name('rsdp_delivery_id_fk'); // nom court
            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('rsdp_product_id_fk'); // nom court
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_stock_delivery_product');
    }
};
