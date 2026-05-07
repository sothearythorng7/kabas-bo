<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'expired', 'archived'])->default('draft');
            $table->enum('activation_mode', ['automatic', 'code_required'])->default('automatic');
            $table->integer('priority')->default(0);
            $table->boolean('is_exclusive')->default(false);
            $table->string('stackable_group')->nullable();
            $table->enum('conditions_logic', ['all', 'any'])->default('all');
            $table->enum('channel', ['website', 'pos', 'both'])->default('website');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('max_uses_total')->nullable();
            $table->unsignedInteger('max_uses_per_customer')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->decimal('max_budget', 13, 5)->nullable();
            $table->decimal('budget_consumed', 13, 5)->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'activation_mode']);
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_rules');
    }
};
