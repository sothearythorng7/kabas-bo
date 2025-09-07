<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Magasin principal (pour ajustement ou sortie/entrée)
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();

            // Magasins source et destination pour les transferts
            $table->foreignId('from_store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->foreignId('to_store_id')->nullable()->constrained('stores')->cascadeOnDelete();

            // Type de mouvement : 'transfer', 'sale', 'adjustment', etc.
            $table->string('type');

            // Statut du mouvement
            $table->string('status')->default('draft');

            // Utilisateur ayant créé le mouvement
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // Note libre pour décrire le mouvement
            $table->text('note')->nullable();

            // Référence optionnelle (ex: commande, facture, réception fournisseur)
            $table->unsignedBigInteger('related_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_items');
        Schema::dropIfExists('stock_movements');
    }
};
