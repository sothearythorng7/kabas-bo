<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_transaction_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('cash_transaction_id')->references('id')->on('cash_transactions')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transaction_items');
    }
};
