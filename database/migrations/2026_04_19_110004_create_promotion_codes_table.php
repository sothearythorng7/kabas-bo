<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_rule_id')->constrained('promotion_rules')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('promotion_rule_id');
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_codes');
    }
};
