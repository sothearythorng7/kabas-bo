import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { db } from '../db/index.js';
import { fetchCatalog } from '../api/endpoints/catalog.js';

/**
 * Catalog state.
 *
 * Persistence strategy:
 *  - On boot, load from Dexie (instant, even offline).
 *  - If online, refresh from `/api/pos/catalog/{storeId}` in background.
 *  - Persist products/giftBoxes/giftCards into the unified `catalog` table,
 *    distinguished by `type` field. Payments + category tree in their own tables.
 *
 * Search rules (match V1):
 *  - 4+ chars alphanumeric → try exact EAN / barcodes match first.
 *  - Otherwise → LIKE on name.fr / name.en.
 */
export const useCatalogStore = defineStore('catalog', () => {
    const products = ref([]);
    const giftBoxes = ref([]);
    const giftCards = ref([]);
    const categoryTree = ref(null);
    const payments = ref([]);
    const lastSyncedAt = ref(null);
    const loading = ref(false);
    const error = ref('');

    const brands = computed(() => {
        const set = new Map();
        for (const p of products.value) {
            const raw = p.brand?.name || p.brand;
            if (!raw) continue;
            const name = typeof raw === 'string' ? raw : (raw.en || raw.fr || '');
            if (!name) continue;
            const display = titleCase(name);
            if (!set.has(display)) set.set(display, { name: display, raw });
        }
        return Array.from(set.values()).sort((a, b) => a.name.localeCompare(b.name));
    });

    function titleCase(str) {
        return String(str)
            .toLowerCase()
            .replace(/\b\w/g, (c) => c.toUpperCase());
    }

    async function loadFromCache() {
        try {
            const [rows, payRows, treeRow] = await Promise.all([
                db.table('catalog').toArray(),
                db.table('payments').toArray(),
                db.table('category_tree').get(1),
            ]);
            const split = { product: [], gift_box: [], gift_card: [] };
            for (const row of rows) {
                if (split[row.type]) split[row.type].push(row);
            }
            products.value = split.product;
            giftBoxes.value = split.gift_box;
            giftCards.value = split.gift_card;
            payments.value = payRows;
            categoryTree.value = treeRow?.tree || null;
            return rows.length > 0;
        } catch (err) {
            console.warn('[POS V2] catalog cache load failed', err);
            return false;
        }
    }

    async function syncFromApi(storeId) {
        loading.value = true;
        error.value = '';
        try {
            const data = await fetchCatalog(storeId);
            const raw = {
                products: Array.isArray(data?.products) ? data.products : [],
                giftBoxes: Array.isArray(data?.giftBoxes) ? data.giftBoxes : [],
                giftCards: Array.isArray(data?.giftCards) ? data.giftCards : [],
                categoryTree: data?.categoryTree || null,
                payments: Array.isArray(data?.paymentsMethod) ? data.paymentsMethod : [],
            };

            const flat = [
                ...raw.products.map((p) => normaliseRow(p, 'product', storeId)),
                ...raw.giftBoxes.map((p) => normaliseRow(p, 'gift_box', storeId)),
                ...raw.giftCards.map((p) => normaliseRow(p, 'gift_card', storeId)),
            ];

            await db.transaction('rw',
                db.table('catalog'),
                db.table('payments'),
                db.table('category_tree'),
                async () => {
                    await db.table('catalog').clear();
                    if (flat.length) await db.table('catalog').bulkPut(flat);
                    await db.table('payments').clear();
                    if (raw.payments.length) await db.table('payments').bulkPut(raw.payments);
                    await db.table('category_tree').put({ id: 1, tree: raw.categoryTree });
                }
            );

            products.value = flat.filter((r) => r.type === 'product');
            giftBoxes.value = flat.filter((r) => r.type === 'gift_box');
            giftCards.value = flat.filter((r) => r.type === 'gift_card');
            payments.value = raw.payments;
            categoryTree.value = raw.categoryTree;
            lastSyncedAt.value = new Date().toISOString();
        } catch (err) {
            console.error('[POS V2] catalog sync failed', err);
            error.value = err.message || 'Sync failed';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    function normaliseRow(item, type, storeId) {
        // Build a unique compound key so products / gift_boxes / gift_cards
        // with overlapping numeric IDs don't collide in the unified `catalog` table.
        const id = `${type}-${item.id}`;
        const barcodes = [];
        if (item.ean) barcodes.push(String(item.ean).toLowerCase());
        if (Array.isArray(item.barcodes)) {
            for (const b of item.barcodes) {
                if (b) barcodes.push(String(b).toLowerCase());
            }
        }
        return {
            id,
            original_id: item.id,
            type,
            ean: item.ean ? String(item.ean) : null,
            barcodes,
            name: normaliseName(item.name),
            description: item.description || null,
            price: Number(item.price ?? item.amount ?? 0),
            price_btob: item.price_btob != null ? Number(item.price_btob) : null,
            brand: item.brand || null,
            categories: item.categories || [],
            photos: item.photos || [],
            total_stock: item.total_stock != null ? Number(item.total_stock) : null,
            is_active: item.is_active !== false,
            store_id: storeId,
            raw: item,
        };
    }

    function normaliseName(name) {
        if (!name) return { en: '', fr: '' };
        if (typeof name === 'string') return { en: name, fr: name };
        return { en: name.en || name.fr || '', fr: name.fr || name.en || '' };
    }

    /**
     * Local search against the in-memory products array.
     * Returns up to `limit` matches, scoring exact EAN first.
     */
    function searchLocal(query, { limit = 50, type = null } = {}) {
        const q = (query || '').trim().toLowerCase();
        if (!q) return [];

        const pool = type === 'product' ? products.value
            : type === 'gift_box' ? giftBoxes.value
            : type === 'gift_card' ? giftCards.value
            : [...products.value, ...giftBoxes.value, ...giftCards.value];

        // Exact EAN / barcode (only when query looks like a code)
        const isCode = q.length >= 4 && /^[\d\-a-z]+$/i.test(q);
        if (isCode) {
            const exact = pool.filter((p) => {
                if (p.ean && String(p.ean).toLowerCase() === q) return true;
                if (p.barcodes && p.barcodes.includes(q)) return true;
                return false;
            });
            if (exact.length) return exact.slice(0, limit);
        }

        // Fallback: name LIKE
        return pool.filter((p) => {
            const fr = (p.name?.fr || '').toLowerCase();
            const en = (p.name?.en || '').toLowerCase();
            return fr.includes(q) || en.includes(q);
        }).slice(0, limit);
    }

    /**
     * Find a single product by exact EAN / barcode (for scanner).
     */
    function findByBarcode(code) {
        const q = (code || '').trim().toLowerCase();
        if (!q) return null;
        const pool = [...products.value, ...giftBoxes.value, ...giftCards.value];
        return pool.find((p) => {
            if (p.ean && String(p.ean).toLowerCase() === q) return true;
            if (p.barcodes && p.barcodes.includes(q)) return true;
            return false;
        }) || null;
    }

    function productsByCategory(categoryId) {
        if (!categoryId) return products.value;
        return products.value.filter((p) => {
            const cats = p.categories || [];
            return cats.some((c) => (c.id === categoryId) || c === categoryId);
        });
    }

    function flattenCategoryTree(node = categoryTree.value, acc = []) {
        if (!node) return acc;
        acc.push({ id: node.id, name: node.name, parentId: node.parent_id || null });
        for (const child of node.children || []) {
            flattenCategoryTree(child, acc);
        }
        return acc;
    }

    return {
        products,
        giftBoxes,
        giftCards,
        categoryTree,
        payments,
        lastSyncedAt,
        loading,
        error,
        brands,
        loadFromCache,
        syncFromApi,
        searchLocal,
        findByBarcode,
        productsByCategory,
        flattenCategoryTree,
    };
});
