<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['pure_loss', 'supplier_refund']);
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'validated', 'refund_requested', 'refund_received'])->default('draft');
            $table->string('reference')->nullable()->unique();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->timestamp('refund_received_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->foreignId('refund_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_loss_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_loss_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('loss_reason')->nullable();
            $table->timestamps();
        });

        // Create the Stock Loss Expense financial account
        DB::table('financial_accounts')->insert([
            'code' => '60001',
            'name' => 'Stock Loss Expense',
            'type' => 'expense',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_loss_items');
        Schema::dropIfExists('stock_losses');
        DB::table('financial_accounts')->where('code', '60001')->delete();
    }
};
