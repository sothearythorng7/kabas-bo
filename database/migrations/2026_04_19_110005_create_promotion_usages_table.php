<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_rule_id')->constrained('promotion_rules')->restrictOnDelete();
            $table->foreignId('promotion_code_id')->nullable()->constrained('promotion_codes')->nullOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('discount_amount', 13, 5)->default(0);
            $table->decimal('gift_cost', 13, 5)->default(0);
            $table->json('snapshot');
            $table->enum('status', ['pending', 'confirmed', 'reverted'])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();

            $table->index(['promotion_rule_id', 'customer_id']);
            $table->index('order_id');
            $table->index('status');
            $table->index('applied_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
    }
};
