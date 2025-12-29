<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->string('attachment')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->string('attachment')->nullable(false)->change();
        });
    }
};
