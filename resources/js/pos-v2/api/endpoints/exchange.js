import { api } from '../client.js';

/**
 * Looks up a past sale by ID and reports per-item exchangeability.
 *
 * V1 contract:
 *   GET /api/pos/exchange/lookup-sale?sale_id={id}
 *   →  {
 *        success: true,
 *        sale: {
 *          id, created_at, store, days_since_purchase, is_exchangeable,
 *          items: [{
 *            sale_item_id, product_id, product_name, quantity, unit_price,
 *            is_exchangeable, exchanged_at, exchange_reason
 *          }, ...]
 *        }
 *      }
 *   Errors:
 *     404 → { success: false, error: 'not_found' }
 *     400 → { success: false, error: 'too_old' }
 */
export function lookupSale(saleId) {
    return api.get(`/api/pos/exchange/lookup-sale?sale_id=${encodeURIComponent(saleId)}`);
}

/**
 * Executes the exchange: marks returned items, decrements stock for new items,
 * creates Exchange + ExchangeItems, generates a voucher if a credit is due.
 *
 * V1 contract:
 *   POST /api/pos/exchange/process
 *   {
 *     original_sale_id, shift_id,
 *     returned_items: [{ sale_item_id, quantity? }, ...],
 *     new_items:      [{ product_id, quantity? }, ...],
 *     payments:       [{ method, amount, voucher_code? }, ...],
 *     notes
 *   }
 *   →  {
 *        success: true,
 *        exchange: { id, return_total, new_items_total, balance,
 *                    payment_received, voucher_generated: { code, amount, expires_at } | null },
 *        updated_sale: {...},
 *        receipt: 'exchange' | 'exchange_with_voucher'
 *      }
 */
export function processExchange(payload) {
    return api.post('/api/pos/exchange/process', payload);
}
