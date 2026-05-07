<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_rule_id')->constrained('promotion_rules')->cascadeOnDelete();
            $table->string('type');
            $table->json('params');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['promotion_rule_id', 'position']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_actions');
    }
};
