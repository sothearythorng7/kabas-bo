/**
 * Dexie schema for the POS V2 offline store.
 *
 * Versions MUST be append-only. Never edit a past version's stores spec —
 * always bump to a new version and write an `upgrade` callback if the
 * schema diverges. Migrating from V1 (which used in-memory + localStorage)
 * is one-shot at boot in `useSync` (TBD Phase 1+).
 */
export function registerSchema(db) {
    db.version(1).stores({
        // Cached user list (synced from /api/pos/users at boot)
        users: 'id, pin_code, store_id',

        // Cached catalog: products + gift boxes + gift cards merged
        // Compound index on [type+ean] for barcode lookups
        catalog: 'id, type, ean, *barcodes, store_id',

        // Payment methods cached from catalog response
        payments: 'id, code',

        // Category tree snapshot (single row, id=1)
        category_tree: 'id',

        // Pending / synced sales — sync queue is filtered by status
        // pos_local_id is the idempotency key sent to backend
        sales_queue: 'pos_local_id, status, shift_id, created_at',

        // Generic key-value settings (cash_in_shift_X, cash_out_shift_X, etc.)
        settings: 'key',
    });
}
