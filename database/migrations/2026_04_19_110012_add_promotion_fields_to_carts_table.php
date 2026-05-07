<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'applied_promotion_code')) {
                $table->string('applied_promotion_code', 64)->nullable()->after('session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'applied_promotion_code')) {
                $table->dropColumn('applied_promotion_code');
            }
        });
    }
};
