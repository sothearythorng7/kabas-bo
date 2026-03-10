<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_country_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_from', 8, 2);
            $table->decimal('weight_to', 8, 2);
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['shipping_country_id', 'weight_from', 'weight_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
