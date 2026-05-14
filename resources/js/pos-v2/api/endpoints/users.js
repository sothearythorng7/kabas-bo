import { api } from '../client.js';

/**
 * Returns all POS users with their PIN code and store_id.
 * V1 contract: array of { id, name, pin_code, store_id }.
 */
export function fetchUsers() {
    return api.get('/api/pos/users');
}
