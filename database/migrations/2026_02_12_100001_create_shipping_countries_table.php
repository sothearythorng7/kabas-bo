<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->string('name', 100);
            $table->string('continent', 30);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('continent');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_countries');
    }
};
