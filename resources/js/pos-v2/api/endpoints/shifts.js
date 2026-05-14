import { api } from '../client.js';

/**
 * Returns the currently open shift for a given user, or null.
 */
export function fetchCurrentShift(userId) {
    return api.get(`/api/pos/shifts/current/${userId}`);
}

/**
 * Returns the expected cash for a given user (opening + cash sales + in - out).
 */
export function fetchExpectedCash(userId) {
    return api.get(`/api/pos/shifts/expected-cash/${userId}`);
}

/**
 * Opens a new shift.
 * @param {{ user_id: number, start_amount: number, popup_event_id?: number|null }} payload
 */
export function startShift(payload) {
    return api.post('/api/pos/shifts/start', payload);
}

/**
 * Closes the current shift.
 * @param {{
 *   user_id: number,
 *   end_amount: number,
 *   visitors_count?: number,
 *   cash_difference?: number,
 *   cash_in?: number,
 *   cash_out?: number,
 * }} payload
 */
export function endShift(payload) {
    return api.post('/api/pos/shifts/end', payload);
}

/**
 * Switches the cashier in the current shift (closes old ShiftUser, opens new).
 * @param {{ shift_id: number, old_user_id: number, new_user_id: number }} payload
 */
export function changeShiftUser(payload) {
    return api.post('/api/pos/shifts/change-user', payload);
}

/**
 * Returns shifts + sales for a given date (used by Journal).
 */
export function fetchSalesByDate(payload) {
    return api.post('/api/pos/shifts/sales-by-date', payload);
}
