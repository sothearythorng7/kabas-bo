<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_stock_batches', function (Blueprint $table) {
            // rendre reseller_id nullable
            $table->unsignedBigInteger('reseller_id')->nullable()->change();

            // ajouter store_id nullable
            $table->unsignedBigInteger('store_id')->nullable()->after('reseller_id');

            // optionnel : ajouter clé étrangère pour store si tu as une table stores
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_stock_batches', function (Blueprint $table) {
            // remettre reseller_id obligatoire
            $table->unsignedBigInteger('reseller_id')->nullable(false)->change();

            // supprimer store_id
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
