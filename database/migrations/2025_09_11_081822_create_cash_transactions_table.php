<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('external_id')->nullable()->index();
            $table->enum('payment_method', ['cash','card','other'])->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->json('meta')->nullable();
            $table->dateTime('transacted_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
