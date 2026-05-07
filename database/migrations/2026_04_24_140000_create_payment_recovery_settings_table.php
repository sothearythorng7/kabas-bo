<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_recovery_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('delay_hours')->default(24);
            $table->unsignedInteger('link_validity_days')->default(7);
            $table->json('subject')->nullable();
            $table->json('heading')->nullable();
            $table->json('intro_body')->nullable();
            $table->json('cta_label')->nullable();
            $table->json('footer_text')->nullable();
            $table->timestamps();
        });

        DB::table('payment_recovery_settings')->insert([
            'enabled' => false,
            'delay_hours' => 24,
            'link_validity_days' => 7,
            'subject' => json_encode([
                'fr' => 'Finalisez votre paiement — votre commande vous attend',
                'en' => 'Complete your payment — your order is waiting',
            ]),
            'heading' => json_encode([
                'fr' => 'Votre commande vous attend',
                'en' => 'Your order is waiting',
            ]),
            'intro_body' => json_encode([
                'fr' => "Bonjour,\n\nVotre commande <strong>:order_number</strong> n'a pas été finalisée. Votre panier est réservé, il ne vous reste qu'à régler pour confirmer.",
                'en' => "Hello,\n\nYour order <strong>:order_number</strong> was not finalized. Your items are reserved — you just need to complete the payment to confirm.",
            ]),
            'cta_label' => json_encode([
                'fr' => 'Finaliser mon paiement',
                'en' => 'Complete my payment',
            ]),
            'footer_text' => json_encode([
                'fr' => 'Ce lien est personnel et valable quelques jours. Après cette période, la commande sera automatiquement annulée.',
                'en' => 'This link is personal and valid for a few days. After that, the order will be automatically cancelled.',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_recovery_settings');
    }
};
