<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variation_values', function (Blueprint $table) {
            $table->json('audit_decision')->nullable()->after('color_hex');
            $table->timestamp('audit_decided_at')->nullable()->after('audit_decision');
        });
    }

    public function down(): void
    {
        Schema::table('variation_values', function (Blueprint $table) {
            $table->dropColumn(['audit_decision', 'audit_decided_at']);
        });
    }
};
