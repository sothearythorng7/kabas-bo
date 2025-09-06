<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // supprimer l'ancien flag
            if (Schema::hasColumn('stores', 'is_warehouse')) {
                $table->dropColumn('is_warehouse');
            }

            // ajouter le champ enum
            $table->enum('type', ['shop', 'warehouse'])->default('shop')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->boolean('is_warehouse')->default(false);
        });
    }
};
