<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_cart_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->enum('discount_type', ['percent', 'amount'])->default('percent');
            $table->decimal('discount_value', 10, 2)->default(10);
            $table->unsignedInteger('validity_days')->default(7);
            $table->unsignedBigInteger('promotion_rule_id')->nullable();
            $table->timestamps();
        });

        DB::table('abandoned_cart_settings')->insert([
            'enabled' => false,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'validity_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_cart_settings');
    }
};
