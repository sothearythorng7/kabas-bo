import { api } from '../client.js';

/**
 * Returns the full catalog snapshot for a store.
 * V1 response shape:
 * {
 *   products:        [{ id, ean, barcodes, name:{fr,en}, price, price_btob, brand, categories, photos, total_stock, ... }],
 *   giftBoxes:       [{ id, name:{}, price, ... }],
 *   giftCards:       [{ id, name:{}, amount, is_active }],
 *   categoryTree:    { id, name:{}, children: [...] },
 *   paymentsMethod:  [{ id, name, code }],
 *   stores:          [{ id, name }]
 * }
 */
export function fetchCatalog(storeId) {
    return api.get(`/api/pos/catalog/${storeId}`);
}

/**
 * Server-side search fallback when local catalog cache is incomplete.
 * Default flow uses Dexie tables filled by `fetchCatalog`.
 */
export function searchCatalog(storeId, query) {
    const q = encodeURIComponent(query);
    return api.get(`/api/pos/search/${storeId}?q=${q}`);
}
