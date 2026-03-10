<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE salary_adjustments MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE salary_adjustments MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
