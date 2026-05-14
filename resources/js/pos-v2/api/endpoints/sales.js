import { api } from '../client.js';

/**
 * Pushes a batch of finalized sales to the backend.
 *
 * V1 contract:
 *   POST /api/pos/sales/sync
 *   {
 *     shift_id: int,
 *     sales: [{
 *       id: 'local_uuid',         // pos_local_id, idempotency key
 *       payment_type, total, discounts, split_payments,
 *       items: [{ product_id, original_id, type, quantity, price,
 *                 discounts, is_delivery, delivery_address,
 *                 is_custom_service, custom_service_description,
 *                 generated_code }, ...]
 *     }]
 *   }
 *
 * Response:
 *   {
 *     status: 'success',
 *     synced_sales: [local_id, ...],
 *     sales_mapping: {
 *       local_id: { sale_id, items: [{ product_id, sale_item_id }] }
 *     }
 *   }
 *
 * Idempotency: re-syncing the same pos_local_id is a no-op server-side
 * and still returns the mapping, so retries on flaky network are safe.
 */
export function syncSales(payload) {
    return api.post('/api/pos/sales/sync', payload);
}
