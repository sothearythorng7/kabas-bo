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
        Schema::create('gift_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('ean')->unique()->nullable();
            $table->json('name'); // { "fr": "...", "en": "..." }
            $table->json('description'); // { "fr": "...", "en": "..." }
            $table->json('slugs'); // { "fr": "...", "en": "..." }
            $table->decimal('price', 10, 2);
            $table->decimal('price_btob', 10, 2)->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_best_seller')->default(false);
            $table->json('attributes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_boxes');
    }
};
