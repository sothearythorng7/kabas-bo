<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reactivate 4 shipping countries that were deactivated by a false-positive
     * run of the Cambodia Post sync cron on 2026-05-10 05:00:33 UTC.
     *
     * Root cause: SyncCambodiaPostRates::updateCountryStatus() deactivated
     * any country missing from the API response, even though all of the
     * country's rates came from Cambodia Post carriers (EMS/Parcel/Letter/ePacket)
     * mapped under SERVICE_CARRIER_MAP, making the "has other carrier rates"
     * guard effectively a no-op for ASEAN countries.
     *
     * Affected countries (all deactivated at exactly 2026-05-10 05:00:33 UTC):
     *   - id=37  NA Namibia
     *   - id=61  BN Brunei
     *   - id=78  MY Malaysia
     *   - id=124 LV Latvia
     *
     * Each still has valid, recent rates (refresh ~2026-05-03) from Cambodia
     * Post carriers. last_seen_in_cp_sync_at is seeded to now() so the
     * anti-flap guard (see same PR) does not immediately deactivate them
     * on the next Sunday run.
     *
     * Companion code change: anti-flap logic in
     * app/Console/Commands/SyncCambodiaPostRates.php (only deactivate after
     * 8+ days of consecutive misses).
     */
    public function up(): void
    {
        DB::table('shipping_countries')
            ->whereIn('id', [37, 61, 78, 124])
            ->where('is_active', false)
            ->update([
                'is_active'              => true,
                'last_seen_in_cp_sync_at' => now(),
                'updated_at'             => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('shipping_countries')
            ->whereIn('id', [37, 61, 78, 124])
            ->update([
                'is_active'              => false,
                'last_seen_in_cp_sync_at' => null,
                'updated_at'             => now(),
            ]);
    }
};
