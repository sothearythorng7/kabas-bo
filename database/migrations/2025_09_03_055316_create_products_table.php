<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('ean')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('slugs');
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_best_seller')->default(false);
            $table->json('attributes')->nullable(); // si besoin plus tard
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
