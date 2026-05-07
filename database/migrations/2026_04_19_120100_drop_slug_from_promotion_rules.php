<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_rules', 'slug')) {
                $table->dropUnique('promotion_rules_slug_unique');
                $table->dropColumn('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });
    }
};
