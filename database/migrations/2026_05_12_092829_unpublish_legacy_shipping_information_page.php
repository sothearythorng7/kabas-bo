<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Soft-delete the legacy "Shipping Information" CMS page (id=2).
     *
     * Why: replaced by the new interactive /shipping-rates route (PR site #X).
     * Setting is_published=0 makes the page disappear from:
     *   - the public footer (composer filters on is_published)
     *   - the public sitemap (SitemapController filters on is_published)
     * while keeping the row in DB for historical reference.
     */
    public function up(): void
    {
        DB::table('pages')
            ->where('id', 2)
            ->where('is_published', 1)
            ->update([
                'is_published' => 0,
                'updated_at'   => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('pages')
            ->where('id', 2)
            ->update([
                'is_published' => 1,
                'updated_at'   => now(),
            ]);
    }
};
