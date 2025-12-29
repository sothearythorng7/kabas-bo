<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_consumed', 12, 4); // quantité réellement consommée
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_consumptions');
    }
};
