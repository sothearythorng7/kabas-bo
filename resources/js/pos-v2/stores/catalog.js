import { defineStore } from 'pinia';
import { ref } from 'vue';

/**
 * Catalog state stub — Phase 3 will populate this from /api/pos/catalog/{storeId}
 * (products, gift boxes, gift cards, category tree, payment methods).
 */
export const useCatalogStore = defineStore('catalog', () => {
    const products = ref([]);
    const giftBoxes = ref([]);
    const giftCards = ref([]);
    const categoryTree = ref(null);
    const payments = ref([]);
    const lastSyncedAt = ref(null);

    return {
        products,
        giftBoxes,
        giftCards,
        categoryTree,
        payments,
        lastSyncedAt,
    };
});
