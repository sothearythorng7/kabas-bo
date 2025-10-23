<?php

// database/migrations/2025_10_22_000000_create_pages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->json('title');
            $t->json('content')->nullable();
            $t->json('slugs');               // { "fr": "cgv", "en": "terms" }
            $t->json('meta_title')->nullable();
            $t->json('meta_description')->nullable();
            $t->boolean('is_published')->default(false)->index();
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pages');
    }
};
