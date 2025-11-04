<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gift_card_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->onDelete('cascade');
            $table->string('code', 50)->unique(); // Code unique généré
            $table->decimal('original_amount', 10, 2); // Montant initial
            $table->decimal('remaining_amount', 10, 2); // Montant restant (peut être partiellement utilisé)
            $table->boolean('is_active')->default(true);
            $table->timestamp('used_at')->nullable(); // Date d'utilisation complète
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null'); // Commande qui a généré ce code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_card_codes');
    }
};
