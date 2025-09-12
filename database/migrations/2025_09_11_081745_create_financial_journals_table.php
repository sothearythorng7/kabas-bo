<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('type')->index();
            $table->dateTime('occurred_at')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_journals');
    }
};
