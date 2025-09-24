<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_cash', 10, 2)->nullable();   // montant caisse au début
            $table->decimal('closing_cash', 10, 2)->nullable();   // montant caisse à la fin
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('synced')->default(false); // pour savoir si shift est déjà sync
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
