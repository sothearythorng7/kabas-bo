<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE supplier_returns MODIFY COLUMN status ENUM('draft', 'pending', 'validated') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE supplier_returns MODIFY COLUMN status ENUM('draft', 'validated') NOT NULL DEFAULT 'draft'");
    }
};
