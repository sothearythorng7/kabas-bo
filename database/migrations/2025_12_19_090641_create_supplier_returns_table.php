<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'validated'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_return_items');
        Schema::dropIfExists('supplier_returns');
    }
};
