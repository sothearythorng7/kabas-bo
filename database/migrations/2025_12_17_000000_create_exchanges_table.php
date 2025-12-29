<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();

            // Original sale reference
            $table->foreignId('original_sale_id')->constrained('sales')->cascadeOnDelete();

            // Location & staff
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Amounts
            $table->decimal('return_total', 10, 2);
            $table->decimal('new_items_total', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);

            // Payment for difference (if customer owes)
            $table->string('payment_method', 20)->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('payment_voucher_id')->nullable();

            // Generated voucher (if store owes customer)
            $table->unsignedBigInteger('generated_voucher_id')->nullable();

            // Related sale (for new items)
            $table->foreignId('new_sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchanges');
    }
};
