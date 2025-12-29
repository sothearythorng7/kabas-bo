<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('raw_material_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->date('received_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_stock_batches');
    }
};
