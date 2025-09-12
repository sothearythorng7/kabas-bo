<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        // Seed de base
        DB::table('financial_payment_methods')->insert([
            ['name' => 'Cash', 'code' => 'CASH', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Virement', 'code' => 'WIRE', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_payment_methods');
    }
};
