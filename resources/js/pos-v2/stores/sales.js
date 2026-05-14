import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { db } from '../db/index.js';

/**
 * Sales queue / sync state.
 *
 * Tracks pending sales waiting for upload to `/api/pos/sales/sync`.
 * Phase 4 will implement the actual sync loop with idempotency
 * (pos_local_id → real sale_id mapping).
 *
 * For Phase 3 we only expose counts so the TopBar sync badge can
 * render the number of queued sales (excluding the active draft).
 */
export const useSalesStore = defineStore('sales', () => {
    const pendingCount = ref(0);
    const validatedCount = ref(0);
    const lastSyncAt = ref(null);

    async function refreshCounts() {
        try {
            const all = await db.table('sales_queue').toArray();
            pendingCount.value = all.filter((s) => s.status === 'pending' || s.status === 'syncing' || s.status === 'error').length;
            validatedCount.value = all.filter((s) => s.status === 'synced').length;
        } catch (err) {
            console.warn('[POS V2] sales counts refresh failed', err);
        }
    }

    return {
        pendingCount,
        validatedCount,
        lastSyncAt,
        refreshCounts,
    };
});
