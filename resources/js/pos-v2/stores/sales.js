import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { db } from '../db/index.js';
import { syncSales } from '../api/endpoints/sales.js';

/**
 * Sales queue management + sync orchestration.
 *
 * Status lifecycle (status column on `sales_queue`):
 *   draft   → being edited by the cashier (single row managed by the cart store)
 *   pending → sale was paid, waiting for upload
 *   syncing → upload in flight
 *   synced  → server confirmed (real sale_id is stored under `remote.sale_id`)
 *   error   → upload failed, will retry next cycle
 *
 * Idempotency:
 *   `pos_local_id` is the row primary key (the UUID from cart.activeSale.id).
 *   Re-syncing is safe; backend returns the same mapping.
 */
export const useSalesStore = defineStore('sales', () => {
    const pending = ref([]);
    const synced = ref([]);
    const lastSyncAt = ref(null);
    const lastError = ref(null);
    const syncing = ref(false);

    const pendingCount = computed(() => pending.value.length);

    async function refresh() {
        try {
            const all = await db.table('sales_queue').toArray();
            pending.value = all.filter((s) => ['pending', 'syncing', 'error'].includes(s.status));
            synced.value = all.filter((s) => s.status === 'synced');
        } catch (err) {
            console.warn('[POS V2] sales refresh failed', err);
        }
    }

    /**
     * Total cash collected during a shift, summing the CASH portion of
     * every paid sale (pending + synced). Voucher-paid amounts are excluded
     * (they don't move money in the drawer). Drafts are excluded — sale is
     * only counted once payment has been confirmed.
     */
    function cashSalesForShift(shiftId) {
        const counted = [...pending.value, ...synced.value]
            .filter((s) => s.shift_id === shiftId);
        let total = 0;
        for (const sale of counted) {
            const splits = Array.isArray(sale.split_payments) ? sale.split_payments : [];
            for (const sp of splits) {
                const method = String(sp.payment_type || sp.method || '').toUpperCase();
                if (method === 'CASH' || method === 'ESPÈCES' || method === 'ESPECES') {
                    total += Number(sp.amount || 0);
                }
            }
        }
        return Math.round(total * 100) / 100;
    }

    async function enqueue(sale) {
        const row = { ...sale, status: 'pending', updated_at: new Date().toISOString() };
        await db.table('sales_queue').put(row);
        await refresh();
    }

    /**
     * Sync all pending sales for the given shift to the backend.
     * Returns the response payload (or null on failure).
     */
    async function syncNow(shiftId) {
        if (syncing.value) return null;
        if (!shiftId) return null;

        const toSync = pending.value
            .filter((s) => s.shift_id === shiftId && s.status !== 'syncing')
            .map((s) => stripForUpload(s));

        if (toSync.length === 0) return null;

        syncing.value = true;
        lastError.value = null;

        // Mark as 'syncing' so concurrent triggers don't double-upload.
        await db.transaction('rw', db.table('sales_queue'), async () => {
            for (const s of toSync) {
                const row = await db.table('sales_queue').get(s.id);
                if (row) {
                    await db.table('sales_queue').put({ ...row, status: 'syncing' });
                }
            }
        });

        try {
            const response = await syncSales({ shift_id: shiftId, sales: toSync });
            const mapping = response?.sales_mapping || {};

            await db.transaction('rw', db.table('sales_queue'), async () => {
                for (const localId of Object.keys(mapping)) {
                    const row = await db.table('sales_queue').get(localId);
                    if (!row) continue;
                    await db.table('sales_queue').put({
                        ...row,
                        status: 'synced',
                        remote: mapping[localId],
                        synced_at: new Date().toISOString(),
                    });
                }
                // Anything still 'syncing' that wasn't in the mapping → back to 'pending'.
                const stuck = await db.table('sales_queue').where('status').equals('syncing').toArray();
                for (const row of stuck) {
                    await db.table('sales_queue').put({ ...row, status: 'pending' });
                }
            });

            lastSyncAt.value = new Date().toISOString();
            await refresh();
            return response;
        } catch (err) {
            console.error('[POS V2] sync failed', err);
            lastError.value = err.message || 'Sync failed';
            // Revert syncing → error so they retry next cycle.
            await db.transaction('rw', db.table('sales_queue'), async () => {
                const stuck = await db.table('sales_queue').where('status').equals('syncing').toArray();
                for (const row of stuck) {
                    await db.table('sales_queue').put({
                        ...row,
                        status: 'error',
                        last_error: lastError.value,
                    });
                }
            });
            await refresh();
            return null;
        } finally {
            syncing.value = false;
        }
    }

    return {
        pending,
        synced,
        pendingCount,
        lastSyncAt,
        lastError,
        syncing,
        refresh,
        enqueue,
        syncNow,
        cashSalesForShift,
    };
});

/**
 * Trim cart-only fields before upload (keep payload lean and stable).
 */
function stripForUpload(row) {
    const items = (row.items || []).map((it) => ({
        product_id: it.product_id ?? null,
        original_id: it.original_id ?? null,
        type: it.type,
        quantity: it.quantity,
        price: it.price,
        discounts: it.discounts || [],
        is_delivery: !!it.is_delivery,
        delivery_address: it.delivery_address || null,
        is_custom_service: !!it.is_custom_service,
        custom_service_description: it.custom_service_description || null,
        generated_code: it.generated_code || null,
    }));
    return {
        id: row.id,
        pos_local_id: row.id,
        payment_type: row.payment_type,
        total: row.total,
        discounts: row.discounts || [],
        split_payments: row.split_payments || [],
        items,
        date: row.finalized_at || row.created_at || row.date,
        seller: row.seller || null,
    };
}
