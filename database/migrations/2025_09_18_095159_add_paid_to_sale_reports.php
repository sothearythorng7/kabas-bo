<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_reports', function (Blueprint $table) {
            // On change l'ENUM pour inclure les nouveaux statuts
            $table->enum('status', ['draft', 'validated', 'invoiced', 'waiting_payment', 'paid'])
                  ->default('draft')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_reports', function (Blueprint $table) {
            // On revient à l'ancien ENUM
            $table->enum('status', ['draft', 'validated', 'invoiced'])
                  ->default('draft')
                  ->change();
        });
    }
};
