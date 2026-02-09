<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['vacation', 'sick', 'dayoff']);
            $table->year('year');
            $table->decimal('annual_quota', 5, 2)->default(0);
            $table->decimal('monthly_accrual', 5, 2)->default(0);
            $table->decimal('carryover_days', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_quotas');
    }
};
