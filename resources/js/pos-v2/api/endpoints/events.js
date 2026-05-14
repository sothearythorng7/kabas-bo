import { api } from '../client.js';

/**
 * Returns the active popup events for a given store (selectable at shift start).
 */
export function fetchActiveEvents(storeId) {
    return api.get(`/api/pos/events/active/${storeId}`);
}
