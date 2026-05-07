<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_cart_settings', function (Blueprint $table) {
            $table->json('subject')->nullable()->after('validity_days');
            $table->json('heading')->nullable()->after('subject');
            $table->json('intro_body')->nullable()->after('heading');
            $table->json('cta_label')->nullable()->after('intro_body');
            $table->json('footer_text')->nullable()->after('cta_label');
        });

        DB::table('abandoned_cart_settings')->where('id', 1)->update([
            'subject' => json_encode([
                'fr' => 'Votre panier vous attend — un code promo pour vous',
                'en' => 'Your cart is waiting — a promo code for you',
            ]),
            'heading' => json_encode([
                'fr' => 'Votre panier vous attend',
                'en' => 'Your cart is waiting',
            ]),
            'intro_body' => json_encode([
                'fr' => "Bonjour,\n\nVous avez laissé quelques articles dans votre panier. Pour vous aider à finaliser votre commande, voici un code promo <strong>:discount</strong> valable uniquement pour vous.",
                'en' => "Hello,\n\nYou left a few items in your cart. To help you complete your order, here is a <strong>:discount</strong> promo code, just for you.",
            ]),
            'cta_label' => json_encode([
                'fr' => 'Reprendre mon panier',
                'en' => 'Resume my cart',
            ]),
            'footer_text' => json_encode([
                'fr' => 'Appliquez le code au moment du paiement pour bénéficier de votre réduction.',
                'en' => 'Apply the code at checkout to get your discount.',
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('abandoned_cart_settings', function (Blueprint $table) {
            $table->dropColumn(['subject', 'heading', 'intro_body', 'cta_label', 'footer_text']);
        });
    }
};
