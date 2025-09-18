<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_reports', function (Blueprint $table) {
            // Supprimer l'ancienne colonne enum si elle existe
            $table->dropColumn('status');

            // Ajouter la nouvelle colonne status limitée à waiting_invoice et invoiced
            $table->enum('status', ['waiting_invoice', 'invoiced'])
                  ->default('waiting_invoice')
                  ->after('period_end');

        });
    }

    public function down(): void
    {
        Schema::table('sale_reports', function (Blueprint $table) {

            // Restaurer l'ancienne colonne si nécessaire (draft, validated, invoiced)
            $table->enum('status', ['draft', 'validated', 'invoiced'])
                  ->default('draft')
                  ->after('period_end');
        });
    }
};
