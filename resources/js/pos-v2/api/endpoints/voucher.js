import { api } from '../client.js';

/**
 * Validates a voucher code without consuming it.
 *
 * V1 response shape:
 *   { success: true, amount: float, expires_at: ISO, status: 'active' }
 *   or
 *   { success: false, error: 'Invalid code' | 'expired' | 'used' }
 */
export function validateVoucher(code) {
    const q = encodeURIComponent(code);
    return api.get(`/api/pos/voucher/validate?code=${q}`);
}

/**
 * Optional pre-apply hook (V1 has POST /api/pos/voucher/apply).
 * In practice we mark the voucher used at sale-sync time, but this
 * endpoint exists for early reservation if needed.
 */
export function applyVoucher(payload) {
    return api.post('/api/pos/voucher/apply', payload);
}
