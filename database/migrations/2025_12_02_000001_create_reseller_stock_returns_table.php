<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reseller_stock_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete(); // Pour les shops
            $table->foreignId('destination_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'validated', 'cancelled'])->default('draft');
            $table->text('note')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reseller_stock_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_stock_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_stock_return_items');
        Schema::dropIfExists('reseller_stock_returns');
    }
};
