<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('raw_material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_stock_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2); // positif = entrée, négatif = sortie
            $table->string('type'); // 'purchase', 'production', 'adjustment', 'loss'
            $table->string('reference')->nullable(); // numéro de facture, production_id, etc.
            $table->morphs('source'); // polymorphic: production, adjustment, etc.
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_stock_movements');
    }
};
