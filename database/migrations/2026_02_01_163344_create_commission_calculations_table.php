<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_commission_id')->constrained()->onDelete('cascade');
            $table->string('period', 7); // YYYY-MM format
            $table->decimal('base_amount', 12, 2); // CA used for calculation
            $table->decimal('commission_amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_calculations');
    }
};
