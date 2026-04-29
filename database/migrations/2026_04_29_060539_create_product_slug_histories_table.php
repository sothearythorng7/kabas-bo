<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_slug_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('locale', 8);
            $table->string('old_slug');
            $table->string('new_slug');
            $table->timestamps();

            $table->index(['locale', 'old_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slug_histories');
    }
};
