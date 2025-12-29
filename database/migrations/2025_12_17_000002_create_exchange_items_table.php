<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained()->cascadeOnDelete();

            // What was returned
            $table->foreignId('original_sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            // Stock tracking
            $table->foreignId('stock_batch_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_items');
    }
};
