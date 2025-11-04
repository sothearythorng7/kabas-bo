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
        Schema::create('home_content', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 'presentation_text'
            $table->json('value'); // {"fr": "texte français", "en": "english text"}
            $table->timestamps();
        });

        // Insert default presentation text
        DB::table('home_content')->insert([
            'key' => 'presentation_text',
            'value' => json_encode([
                'fr' => 'Bienvenue chez Kabas Concept Store. Découvrez nos produits artisanaux fabriqués au Cambodge avec passion et savoir-faire.',
                'en' => 'Welcome to Kabas Concept Store. Discover our handcrafted products made in Cambodia with passion and expertise.',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_content');
    }
};
