<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');

            // Source tracking
            $table->enum('source_type', ['exchange', 'manual', 'promotion']);
            $table->foreignId('source_exchange_id')->nullable()->constrained('exchanges')->nullOnDelete();

            // Usage tracking
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_in_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('used_at_store_id')->nullable()->constrained('stores')->nullOnDelete();

            // Validity
            $table->timestamp('expires_at');

            // Audit
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_at_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
