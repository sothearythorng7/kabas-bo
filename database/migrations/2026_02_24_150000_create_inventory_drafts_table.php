<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('location_type'); // store or reseller
            $table->unsignedBigInteger('location_id');
            $table->json('counts');
            $table->timestamps();

            $table->unique(['location_type', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_drafts');
    }
};
