<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track the last time each country was observed in the Cambodia Post
     * sync results. Used by SyncCambodiaPostRates::updateCountryStatus()
     * to implement anti-flap: a country missing from a single sync run
     * (e.g. transient API timeout) is no longer immediately deactivated.
     */
    public function up(): void
    {
        Schema::table('shipping_countries', function (Blueprint $table) {
            $table->timestamp('last_seen_in_cp_sync_at')->nullable()->after('is_active');
            $table->index('last_seen_in_cp_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_countries', function (Blueprint $table) {
            $table->dropIndex(['last_seen_in_cp_sync_at']);
            $table->dropColumn('last_seen_in_cp_sync_at');
        });
    }
};
