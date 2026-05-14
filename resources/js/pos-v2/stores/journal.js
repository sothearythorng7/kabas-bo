import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchSalesByDate } from '../api/endpoints/shifts.js';

/**
 * Journal store — caches the last search result so the SaleDetailView
 * can read its target sale without refetching.
 *
 * V1 contract:
 *   POST /api/pos/shifts/sales-by-date  { search_date, user_id?, store_id? }
 *   →  { shift, sales: [...] }
 *
 * Some BO responses wrap the same payload under different keys depending
 * on the controller version; the loader below normalises that shape.
 */
export const useJournalStore = defineStore('journal', () => {
    const searching = ref(false);
    const error = ref('');
    const searchDate = ref(null);
    const shift = ref(null);
    const sales = ref([]);

    async function search({ date, userId = null, storeId = null }) {
        if (!date) return;
        searching.value = true;
        error.value = '';
        searchDate.value = date;
        try {
            const res = await fetchSalesByDate({
                search_date: date,
                user_id: userId,
                store_id: storeId,
            });
            shift.value = res?.shift ?? null;
            sales.value = Array.isArray(res?.sales) ? res.sales : [];
        } catch (err) {
            console.error('[POS V2] journal search failed', err);
            error.value = err.message || 'Search failed';
            shift.value = null;
            sales.value = [];
        } finally {
            searching.value = false;
        }
    }

    function getSale(id) {
        if (!id) return null;
        return sales.value.find((s) => String(s.id) === String(id)) || null;
    }

    function reset() {
        searchDate.value = null;
        shift.value = null;
        sales.value = [];
        error.value = '';
    }

    return {
        searching,
        error,
        searchDate,
        shift,
        sales,
        search,
        getSale,
        reset,
    };
});
