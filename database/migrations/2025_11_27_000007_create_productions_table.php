<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_produced'); // nombre d'unités produites
            $table->date('produced_at');
            $table->string('batch_number')->nullable(); // numéro de lot de production
            $table->string('status')->default('completed'); // draft, in_progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // qui a enregistré
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
