import { onMounted, onBeforeUnmount, watch } from 'vue';
import { useSessionStore } from '../stores/session.js';
import { useSalesStore } from '../stores/sales.js';
import { useCashStore } from '../stores/cash.js';

/**
 * Background sync loop:
 *   - Runs every `intervalMs` (default 60s).
 *   - Fires immediately when network comes back online.
 *   - Idempotent (calls into sales.syncNow which mark/unmark rows).
 *
 * After every sync attempt, the cash store's "cash sales" total is recomputed
 * so the Expected Cash widget and Shift End verification stay accurate.
 *
 * Usage (typically called once in App.vue):
 *   useSync({ intervalMs: 60000 });
 */
export function useSync({ intervalMs = 60_000 } = {}) {
    const session = useSessionStore();
    const sales = useSalesStore();
    const cash = useCashStore();

    let timer = null;

    async function tick() {
        if (!session.currentShift?.id) return;
        await sales.refresh();
        if (sales.pendingCount === 0) {
            recomputeCash();
            return;
        }
        if (!session.isOnline) return;
        await sales.syncNow(session.currentShift.id);
        recomputeCash();
    }

    function recomputeCash() {
        const shiftId = session.currentShift?.id;
        if (!shiftId) return;
        cash.setCashSales(sales.cashSalesForShift(shiftId));
    }

    function start() {
        if (timer) return;
        tick();
        timer = setInterval(tick, intervalMs);
    }

    function stop() {
        if (timer) clearInterval(timer);
        timer = null;
    }

    function handleOnline() {
        tick();
    }

    onMounted(() => {
        start();
        window.addEventListener('online', handleOnline);
    });

    onBeforeUnmount(() => {
        stop();
        window.removeEventListener('online', handleOnline);
    });

    // Restart loop when a new shift opens.
    watch(() => session.currentShift?.id, (newId, oldId) => {
        if (newId && newId !== oldId) tick();
    });

    return { tick, start, stop };
}
